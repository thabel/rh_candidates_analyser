<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use App\Exception\GeminiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;

/**
 * Service pour l'intégration avec l'API Google Gemini
 */
class GeminiAnalysisService
{
    private FilesystemAdapter $cache;
    private string $geminiApiKey;
    private string $projectDir;
    private const CACHE_TTL = 86400; // 24 heures

    public function __construct(
        private HttpClientInterface $httpClient,
        string $geminiApiKey,
        private string $geminiModel,
        private float $geminiTemperature,
        private int $geminiMaxTokens,
        private LoggerInterface $logger,
        string $projectDir
    ) {
        $this->projectDir = $projectDir;
        $this->geminiApiKey = trim($geminiApiKey);
        if (empty($this->geminiApiKey)) {
            throw new \RuntimeException('Missing GEMINI_API_KEY environment variable');
        }

        $this->cache = new FilesystemAdapter('gemini_cache', self::CACHE_TTL);
    }

    /**
     * Analyse une candidature en comparant le CV avec la fiche de poste
     *
     * @param string $jobDescription La fiche de poste
     * @param string $cv Le CV du candidat
     * @return array Le scoring avec analyse détaillée
     * @throws GeminiException En cas d'erreur API
     */
    public function analyzeCandidate(string $jobDescription, string $cv): array
    {
        // Limiter la taille du CV et de la fiche de poste pour éviter MAX_TOKENS
        $cv = $this->truncateText($cv, 4000);
        $jobDescription = $this->truncateText($jobDescription, 2000);

        // DEBUG: Log le CV envoyé à Gemini dans un fichier
        $this->logCvDebug($cv, $jobDescription);

        // Générer une clé de cache basée sur les inputs
        $cacheKey = hash('sha256', $jobDescription . '::' . $cv);

        // Vérifier le cache
        $cachedResult = $this->cache->getItem($cacheKey);
        if ($cachedResult->isHit()) {
            $this->logger->info('Résultat trouvé en cache', ['cacheKey' => substr($cacheKey, 0, 8)]);
            return $cachedResult->get();
        }

        $prompt = $this->buildPrompt($jobDescription, $cv);

        try {
            $this->logger->info('Appel API Gemini', [
                'model' => $this->geminiModel,
                'maxTokens' => $this->geminiMaxTokens
            ]);
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent";
 

            $response = $this->httpClient->request('POST', $url, [
                'query' => ['key' => $this->geminiApiKey],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => $this->geminiTemperature,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 3000, // Augmenté pour éviter la troncature
                        'responseMimeType' => 'application/json',
                        'responseSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'score' => [
                                    'type' => 'integer',
                                    'description' => 'Score entre 0 et 100'
                                ],
                                'summary' => [
                                    'type' => 'string',
                                    'description' => 'Résumé en 1-2 phrases'
                                ],
                                'positives' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'description' => 'Liste de 4 points positifs'
                                ],
                                'negatives' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'description' => 'Liste de 3 points négatifs'
                                ]
                            ],
                            'required' => ['score', 'summary', 'positives', 'negatives']
                        ]
                    ]
                ],
                'timeout' => 60
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray();

            $this->logger->info('Réponse Gemini', [
                'statusCode' => $statusCode,
                'finishReason' => $data['candidates'][0]['finishReason'] ?? 'unknown',
                'totalTokens' => $data['usageMetadata']['totalTokenCount'] ?? 0
            ]);

            // Vérifier le code de statut
            if ($statusCode !== 200) {
                $this->handleGeminiError($statusCode, $data);
            }

            // Vérifier la raison de fin
            $finishReason = $data['candidates'][0]['finishReason'] ?? null;
            
            if ($finishReason === 'MAX_TOKENS') {
                throw new GeminiException(
                    'La réponse a été tronquée (limite de tokens atteinte). Essayez de réduire la taille du CV ou de la fiche de poste.',
                    statusCode: 400
                );
            }

            if ($finishReason === 'SAFETY') {
                throw new GeminiException(
                    'Le contenu a été bloqué par les filtres de sécurité de Gemini',
                    statusCode: 403
                );
            }

            if (!in_array($finishReason, ['STOP', null])) {
                throw new GeminiException(
                    "Réponse Gemini incomplète : {$finishReason}",
                    statusCode: 500
                );
            }

            // Extraire le contenu JSON
            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $this->logger->error('Structure de réponse invalide', ['data' => $data]);
                throw new GeminiException('Format de réponse Gemini invalide : pas de contenu texte');
            }

            $responseText = $data['candidates'][0]['content']['parts'][0]['text'];
            
            // Nettoyer le texte (retirer les markdown code blocks si présents)
            $responseText = preg_replace('/^```json\s*|\s*```$/m', '', trim($responseText));
            
            $result = json_decode($responseText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Erreur parsing JSON', [
                    'error' => json_last_error_msg(),
                    'response' => $responseText
                ]);
                throw new GeminiException('Impossible de parser la réponse JSON de Gemini: ' . json_last_error_msg());
            }

            // Valider la structure de la réponse
            $result = $this->validateResponse($result);

            // Mettre en cache le résultat
            $cachedResult->set($result);
            $this->cache->save($cachedResult);

            $this->logger->info('Analyse réussie et mise en cache', ['score' => $result['score']]);

            return $result;

        } catch (HttpExceptionInterface $e) {
            $this->logger->error('Erreur HTTP Gemini', [
                'status' => $e->getResponse()->getStatusCode(),
                'message' => $e->getMessage(),
                'model' => $this->geminiModel
            ]);
            $this->handleGeminiError($e->getResponse()->getStatusCode(), []);
        } catch (\JsonException $e) {
            throw new GeminiException('Erreur de parsing JSON: ' . $e->getMessage());
        }
    }

    /**
     * Tronque le texte à une longueur maximale
     *
     * @param string $text Texte à tronquer
     * @param int $maxLength Longueur maximale en caractères
     * @return string Texte tronqué
     */
    private function truncateText(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $truncated = substr($text, 0, $maxLength);
        // Essayer de tronquer au dernier espace pour éviter de couper un mot
        $lastSpace = strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.8) {
            $truncated = substr($truncated, 0, $lastSpace);
        }

        $this->logger->warning('Texte tronqué pour Gemini', [
            'originalLength' => strlen($text),
            'truncatedLength' => strlen($truncated),
            'maxLength' => $maxLength
        ]);

        return $truncated . "\n[... texte tronqué pour optimisation ...]";
    }

    /**
     * Construire le prompt pour Gemini
     */
    private function buildPrompt(string $jobDescription, string $cv): string
    {
        return <<<PROMPT
Tu es un expert RH spécialisé dans l'analyse de candidatures.
Analyse le CV suivant par rapport à la fiche de poste fournie.

FICHE DE POSTE :
{$jobDescription}

CV DU CANDIDAT :
{$cv}

Fournis une analyse avec :
- score : nombre entier entre 0 et 100
- summary : résumé en 1-2 phrases du candidat par rapport au poste
- positives : exactement 4 points forts du candidat
- negatives : exactement 3 points à améliorer

CRITÈRES DE NOTATION (total 100 points) :
- Compétences techniques (40 points)
- Expérience pertinente (30 points)
- Formation et certifications (15 points)
- Soft skills et progression (15 points)

Sois concis, factuel et basé uniquement sur les informations fournies.
PROMPT;
    }

    /**
     * Valider et normaliser la réponse
     */
    private function validateResponse(array $response): array
    {
        // Assurer que le score est un entier entre 0 et 100
        $score = (int)($response['score'] ?? 0);
        if ($score < 0) $score = 0;
        if ($score > 100) $score = 100;

        // Assurer que les arrays existent
        $positives = array_slice($response['positives'] ?? [], 0, 4);
        while (count($positives) < 4) {
            $positives[] = 'Point positif non fourni';
        }

        $negatives = array_slice($response['negatives'] ?? [], 0, 3);
        while (count($negatives) < 3) {
            $negatives[] = 'Point à améliorer non fourni';
        }

        return [
            'score' => $score,
            'summary' => trim($response['summary'] ?? 'Analyse complétée'),
            'positives' => array_values($positives),
            'negatives' => array_values($negatives)
        ];
    }

    /**
     * Log le CV envoyé à Gemini dans un fichier de debug
     * Fichier: var/cv_debug.log (non inclus dans git)
     */
    private function logCvDebug(string $cv, string $jobDescription): void
    {
        try {
            $debugFile = $this->projectDir . '/var/cv_debug.log';

            $timestamp = date('Y-m-d H:i:s');
            $cvLength = strlen($cv);
            $jobLength = strlen($jobDescription);

            $logContent = <<<LOG
================================================================================
[{$timestamp}] DEBUG - CV ENVOYÉ À GEMINI
================================================================================

📄 LONGUEUR DU CV: {$cvLength} caractères
📋 LONGUEUR FICHE DE POSTE: {$jobLength} caractères

--- CV CONTENT (Premiers 2000 caractères) ---
{$cv}
--- FIN CV ---

--- FICHE DE POSTE (Premiers 1000 caractères) ---
{$jobDescription}
--- FIN FICHE ---

================================================================================

LOG;

            file_put_contents($debugFile, $logContent, FILE_APPEND);
            chmod($debugFile, 0666);

        } catch (\Exception $e) {
            $this->logger->warning('Impossible d\'écrire le fichier de debug', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Gérer les erreurs spécifiques de Gemini
     */
    private function handleGeminiError(int $statusCode, array $data): void
    {
        $messages = [
            400 => 'Requête invalide à Gemini (JSON malformé ou données invalides)',
            403 => 'Clé API Gemini invalide ou accès refusé',
            429 => 'Limite de taux Gemini dépassée. Veuillez réessayer dans quelques moments',
            500 => 'Erreur serveur Google Gemini. Veuillez réessayer',
            503 => 'Service Google Gemini temporairement indisponible'
        ];

        $message = $messages[$statusCode] ?? "Erreur Gemini ($statusCode)";

        // Ajouter les détails de l'erreur si disponibles
        if (isset($data['error']['message'])) {
            $message .= ': ' . $data['error']['message'];
        }

        $this->logger->error('Erreur Gemini détectée', [
            'statusCode' => $statusCode,
            'message' => $message,
            'errorData' => $data['error'] ?? null
        ]);

        throw new GeminiException($message, statusCode: $statusCode);
    }
}