<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Candidate;
use App\Service\GeminiAnalysisService;
use App\Exception\GeminiException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/admin', name: 'admin_')]
class AdminCandidateController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GeminiAnalysisService $geminiService,
        private LoggerInterface $logger
    ) {}

    /**
     * Récupère la liste de toutes les candidatures
     */
    #[Route('/candidates', name: 'list_candidates', methods: ['GET'])]
    public function listCandidates(): JsonResponse
    {
        try {
            $candidates = $this->entityManager->getRepository(Candidate::class)
                ->findAllWithLatest();

            $candidatesList = array_map(function (Candidate $candidate) {
                return [
                    'id' => $candidate->getId()->toString(),
                    'firstName' => $candidate->getFirstName(),
                    'lastName' => $candidate->getLastName(),
                    'email' => $candidate->getEmail(),
                    'status' => $candidate->getStatus(),
                    'score' => $candidate->getScore(),
                    'submittedAt' => $candidate->getSubmittedAt()->format('Y-m-d H:i:s'),
                    'analyzedAt' => $candidate->getAnalyzedAt()?->format('Y-m-d H:i:s'),
                ];
            }, $candidates);

            return $this->json([
                'total' => count($candidatesList),
                'candidates' => $candidatesList
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la listage: ' . $e->getMessage());

            return $this->json(
                ['message' => 'Erreur serveur'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Récupère les candidatures en attente d'analyse
     */
    #[Route('/candidates-pending', name: 'list_pending', methods: ['GET'])]
    public function listPending(): JsonResponse
    {
        try {
            $candidates = $this->entityManager->getRepository(Candidate::class)
                ->findPending();

            $candidatesList = array_map(function (Candidate $candidate) {
                return [
                    'id' => $candidate->getId()->toString(),
                    'firstName' => $candidate->getFirstName(),
                    'lastName' => $candidate->getLastName(),
                    'email' => $candidate->getEmail(),
                    'submittedAt' => $candidate->getSubmittedAt()->format('Y-m-d H:i:s'),
                ];
            }, $candidates);

            return $this->json([
                'total' => count($candidatesList),
                'candidates' => $candidatesList
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du listage des candidatures en attente: ' . $e->getMessage());

            return $this->json(
                ['message' => 'Erreur serveur'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Récupère le détail d'une candidature
     */
    #[Route('/candidate/{id}', name: 'get_candidate_detail', methods: ['GET'])]
    public function getCandidateDetail(string $id): JsonResponse
    {
        try {
            $candidate = $this->entityManager->getRepository(Candidate::class)
                ->find($id);

            if (!$candidate) {
                return $this->json(
                    ['message' => 'Candidature non trouvée'],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }

            return $this->json([
                'id' => $candidate->getId()->toString(),
                'firstName' => $candidate->getFirstName(),
                'lastName' => $candidate->getLastName(),
                'email' => $candidate->getEmail(),
                'cvText' => $candidate->getCvText(),
                'cvFileName' => $candidate->getCvFileName(),
                'status' => $candidate->getStatus(),
                'score' => $candidate->getScore(),
                'analysis' => $candidate->getAnalysisResult(),
                'submittedAt' => $candidate->getSubmittedAt()->format('Y-m-d H:i:s'),
                'analyzedAt' => $candidate->getAnalyzedAt()?->format('Y-m-d H:i:s'),
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du détail: ' . $e->getMessage());

            return $this->json(
                ['message' => 'Erreur serveur'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Lance l'analyse d'une candidature avec une fiche de poste
     * 
     * @param Request $request Requête contenant: jobDescription
     */
    #[Route('/candidate/{id}/analyze', name: 'analyze_candidate', methods: ['POST'])]
    public function analyzeCandidate(string $id, Request $request): JsonResponse
    {
        try {
            $candidate = $this->entityManager->getRepository(Candidate::class)
                ->find($id);

            if (!$candidate) {
                return $this->json(
                    ['message' => 'Candidature non trouvée'],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }

            $data = json_decode($request->getContent(), true);

            if (!isset($data['jobDescription'])) {
                return $this->json(
                    ['message' => 'La fiche de poste est obligatoire'],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            $jobDescription = $data['jobDescription'];

            if (strlen($jobDescription) < 50) {
                return $this->json(
                    ['message' => 'La fiche de poste doit contenir au moins 50 caractères'],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            // Marquer comme en cours d'analyse
            $candidate->setStatus('analyzing');
            $this->entityManager->flush();

            // Lancer l'analyse Gemini
            $this->logger->info('Début de l\'analyse', [
                'candidateId' => $candidate->getId(),
                'email' => $candidate->getEmail()
            ]);

            try {
                $cvTextToAnalyze = $candidate->getCvText();

                // DEBUG: Log what we're actually sending to Gemini
                $this->logger->warning('DEBUG - CV avant analyse', [
                    'candidateId' => $candidate->getId(),
                    'cvLength' => strlen($cvTextToAnalyze ?? ''),
                    'cvPreview' => substr($cvTextToAnalyze ?? '', 0, 100),
                    'cvFileName' => $candidate->getCvFileName(),
                    'isNullOrEmpty' => empty($cvTextToAnalyze)
                ]);

                $analysisResult = $this->geminiService->analyzeCandidate(
                    $jobDescription,
                    $cvTextToAnalyze
                );

                // Mettre à jour la candidature avec les résultats
                $candidate->setAnalysisResult($analysisResult);
                $candidate->setScore($analysisResult['score'] ?? null);
                $candidate->setStatus('analyzed');
                $candidate->setAnalyzedAt(new \DateTimeImmutable());
                $this->entityManager->flush();

                $this->logger->info('Analyse réussie', [
                    'candidateId' => $candidate->getId(),
                    'score' => $analysisResult['score'] ?? 'N/A'
                ]);

                return $this->json([
                    'id' => $candidate->getId()->toString(),
                    'firstName' => $candidate->getFirstName(),
                    'lastName' => $candidate->getLastName(),
                    'email' => $candidate->getEmail(),
                    'status' => $candidate->getStatus(),
                    'score' => $candidate->getScore(),
                    'analysis' => $candidate->getAnalysisResult(),
                    'analyzedAt' => $candidate->getAnalyzedAt()->format('Y-m-d H:i:s'),
                    'message' => 'Analyse réussie'
                ], JsonResponse::HTTP_OK);

            } catch (GeminiException $e) {
                // En cas d'erreur Gemini, revenir au status pending
                $candidate->setStatus('pending');
                $this->entityManager->flush();

                $this->logger->error('Erreur Gemini: ' . $e->getMessage());

                return $this->json(
                    ['message' => 'Erreur lors de l\'analyse IA: ' . $e->getMessage()],
                    JsonResponse::HTTP_BAD_GATEWAY
                );
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'analyse: ' . $e->getMessage());

            return $this->json(
                ['message' => 'Erreur serveur'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Supprime une candidature
     */
    #[Route('/candidate/{id}', name: 'delete_candidate', methods: ['DELETE'])]
    public function deleteCandidate(string $id): JsonResponse
    {
        try {
            $candidate = $this->entityManager->getRepository(Candidate::class)
                ->find($id);

            if (!$candidate) {
                return $this->json(
                    ['message' => 'Candidature non trouvée'],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }

            $this->entityManager->remove($candidate);
            $this->entityManager->flush();

            $this->logger->info('Candidature supprimée', [
                'candidateId' => $id
            ]);

            return $this->json(
                ['message' => 'Candidature supprimée'],
                JsonResponse::HTTP_OK
            );

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression: ' . $e->getMessage());

            return $this->json(
                ['message' => 'Erreur serveur'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
