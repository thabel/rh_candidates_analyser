<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\GeminiAnalysisService;
use App\DTO\CandidateAnalysisRequest;
use App\Exception\GeminiException;
use Psr\Log\LoggerInterface;

class CandidateAnalysisController extends AbstractController
{
    public function __construct(
        private GeminiAnalysisService $geminiService,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * Affiche la page d'accueil de l'application
     */
  
    /**
     * Endpoint API pour analyser une candidature
     *
     * @param Request $request La requête HTTP contenant jobDescription et candidateCV
     * @return JsonResponse La réponse avec le score et l'analyse
     */
    #[Route('/api/analyze-candidate', name: 'api_analyze_candidate', methods: ['POST'])]
    public function analyzeCandidate(Request $request): JsonResponse
    {
        try {
            // Récupérer les données JSON
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(
                    ['message' => 'Requête JSON invalide'],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            // Créer le DTO
            $analysisRequest = new CandidateAnalysisRequest(
                jobDescription: $data['jobDescription'] ?? '',
                candidateCV: $data['candidateCV'] ?? ''
            );

            // Valider
            $errors = $this->validator->validate($analysisRequest);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }

                return $this->json(
                    ['message' => implode(', ', $messages)],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            // Analyser avec Gemini
            $this->logger->info('Début de l\'analyse de candidature', [
                'jobDescriptionLength' => strlen($analysisRequest->getJobDescription()),
                'cvLength' => strlen($analysisRequest->getCandidateCV())
            ]);

            $result = $this->geminiService->analyzeCandidate(
                $analysisRequest->getJobDescription(),
                $analysisRequest->getCandidateCV()
            );

            $this->logger->info('Analyse réussie', ['score' => $result['score'] ?? 'N/A']);

            return $this->json($result, JsonResponse::HTTP_OK, [
                'Access-Control-Allow-Origin' => '*'
            ]);

        } catch (GeminiException $e) {
            $this->logger->error('Erreur Gemini: ' . $e->getMessage());

            return $this->json(
                ['message' => 'Erreur lors de l\'analyse IA: ' . $e->getMessage()],
                JsonResponse::HTTP_BAD_GATEWAY
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur inattendue: ' . $e->getMessage());

            return $this->json(
                ['message' => 'Erreur serveur: ' . $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Health check endpoint
     */
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok', 'service' => 'RH Analyser API']);
    }
}
