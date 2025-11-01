<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Candidate;
use App\DTO\CandidateSubmissionRequest;
use App\DTO\CandidateSubmissionResponse;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CandidateSubmissionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * API endpoint pour soumettre une candidature
     * 
     * @param Request $request Requête contenant: firstName, lastName, email, cvText, cvFileName (optionnel)
     * @return JsonResponse
     */
    #[Route('/api/submit-candidate', name: 'api_submit_candidate', methods: ['POST'])]
    public function submitCandidate(Request $request): JsonResponse
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

            // Créer le DTO de requête
            $submissionRequest = new CandidateSubmissionRequest(
                firstName: $data['firstName'] ?? '',
                lastName: $data['lastName'] ?? '',
                email: $data['email'] ?? '',
                cvText: $data['cvText'] ?? '',
                cvFileName: $data['cvFileName'] ?? null
            );

            // Valider
            $errors = $this->validator->validate($submissionRequest);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }

                return $this->json(
                    ['message' => 'Erreurs de validation', 'errors' => $messages],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            // Vérifier si l'email existe déjà
            $existingCandidate = $this->entityManager->getRepository(Candidate::class)
                ->findByEmail($submissionRequest->email);

            if ($existingCandidate) {
                return $this->json(
                    ['message' => 'Un candidat avec cet email existe déjà'],
                    JsonResponse::HTTP_CONFLICT
                );
            }

            // Créer la candidature
            $candidate = new Candidate();
            $candidate->setFirstName($submissionRequest->firstName);
            $candidate->setLastName($submissionRequest->lastName);
            $candidate->setEmail($submissionRequest->email);
            $candidate->setCvText($submissionRequest->cvText);
            $candidate->setCvFileName($submissionRequest->cvFileName ?? 'cv.pdf');

            // Persister
            $this->entityManager->persist($candidate);
            $this->entityManager->flush();

            $this->logger->info('Nouvelle candidature soumise', [
                'candidateId' => $candidate->getId(),
                'email' => $candidate->getEmail()
            ]);

            // Créer la réponse
            $response = new CandidateSubmissionResponse(
                id: $candidate->getId()->toString(),
                firstName: $candidate->getFirstName(),
                lastName: $candidate->getLastName(),
                email: $candidate->getEmail(),
                status: $candidate->getStatus(),
                message: 'Candidature soumise avec succès',
                submittedAt: $candidate->getSubmittedAt()
            );

            return $this->json($response->toArray(), JsonResponse::HTTP_CREATED);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la soumission: ' . $e->getMessage());

            return $this->json(
                ['message' => 'Erreur serveur: ' . $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Récupère une candidature par ID (pour le candidat)
     */
    #[Route('/api/candidate/{id}', name: 'api_get_candidate', methods: ['GET'])]
    public function getCandidate(string $id): JsonResponse
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
                'status' => $candidate->getStatus(),
                'submittedAt' => $candidate->getSubmittedAt()->format('Y-m-d H:i:s'),
                'score' => $candidate->getScore(),
                'analysis' => $candidate->getAnalysisResult(),
                'analyzedAt' => $candidate->getAnalyzedAt()?->format('Y-m-d H:i:s')
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération: ' . $e->getMessage());

            return $this->json(
                ['message' => 'Erreur serveur'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
