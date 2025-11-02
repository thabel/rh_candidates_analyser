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
    private const CACHE_TTL = 86400; // 24 heures

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $geminiApiKey,
        private string $geminiModel,
        private float $geminiTemperature,
        private int $geminiMaxTokens,
        private LoggerInterface $logger
    ) {
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
            $this->logger->info('Appel API Gemini', ['model' => $this->geminiModel]);

            $response = $this->httpClient->request('POST', "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent", [
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
                        'maxOutputTokens' => $this->geminiMaxTokens,
                        'responseMimeType' => 'application/json'
                    ]
                ],
                'timeout' => 60
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray();

            // Vérifier les erreurs spécifiques à Gemini
            if (!$response->getStatusCode() === 200) {
                $this->handleGeminiError($statusCode, $data);
            }

            // Vérifier si la réponse a été bloquée
            if (isset($data['candidates'][0]['finishReason']) &&
                $data['candidates'][0]['finishReason'] === 'SAFETY') {
                throw new GeminiException(
                    'Le contenu a été bloqué par les filtres de sécurité de Gemini',
                    statusCode: 403
                );
            }

            // Extraire le JSON de la réponse
            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new GeminiException('Format de réponse Gemini invalide');
            }

            $responseText = $data['candidates'][0]['content']['parts'][0]['text'];
            $result = json_decode($responseText, true);

            if (!$result) {
                throw new GeminiException('Impossible de parser la réponse JSON de Gemini');
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
                'message' => $e->getMessage()
            ]);
            $this->handleGeminiError($e->getResponse()->getStatusCode(), []);
        } catch (\JsonException $e) {
            throw new GeminiException('Erreur de parsing JSON: ' . $e->getMessage());
        }
    }

    /**
     * Construire le prompt pour Gemini
     */
    private function buildPrompt(string $jobDescription, string $cv): string
    {
        return <<<PROMPT
Tu es un expert RH spécialisé dans l'analyse de candidatures avec une expertise reconnue en recrutement tech.
Analyse objectivement et professionnellement le CV suivant par rapport à la fiche de poste fournie.

FICHE DE POSTE :
{$jobDescription}

CV DU CANDIDAT :
{$cv}

Réponds UNIQUEMENT avec un objet JSON valide (pas de markdown, pas de texte supplémentaire, pas de code blocks) avec cette structure exacte :
{
  "score": <nombre entier entre 0 et 100>,
  "summary": "<résumé en 1-2 phrases maximum du candidat par rapport au poste>",
  "positives": ["point1", "point2", "point3", "point4"],
  "negatives": ["point1", "point2", "point3"]
}

CRITÈRES DE NOTATION (total 100 points) :
- Compétences techniques (40 points) : Correspondance directe avec les technologies et outils requis
- Expérience pertinente (30 points) : Années d'expérience dans un domaine similaire
- Formation et certifications (15 points) : Diplômes et certifications pertinentes
- Soft skills et progression (15 points) : Leadership, communication, progression de carrière

Instructions strictes :
1. Le score doit être un nombre entre 0 et 100
2. La réponse DOIT être du JSON valide (testable avec json_decode)
3. Fournis EXACTEMENT 4 points positifs et 3 points négatifs
4. Chaque point doit être concis (1-2 phrases) et facile à comprendre
5. Sois factuel et basé sur les données du CV et de la fiche de poste
6. Ne fais jamais de suppositions ; utilise uniquement ce qui est explicitement mentionné

IMPORTANT : Réponds avec UNIQUEMENT le JSON, sans markdown, sans explications supplémentaires.
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
            'positives' => $positives,
            'negatives' => $negatives
        ];
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

        $this->logger->error('Erreur Gemini détectée', [
            'statusCode' => $statusCode,
            'message' => $message
        ]);

        throw new GeminiException($message, statusCode: $statusCode);
    }
}
