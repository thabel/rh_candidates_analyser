<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Candidate;
use App\Entity\JobDescription;
use App\Service\AuthService;
use App\Service\GeminiAnalysisService;
use App\Service\NotificationService;
use App\Exception\GeminiException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

#[Route('/admin', name: 'admin_')]
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private EntityManagerInterface $entityManager,
        private GeminiAnalysisService $geminiService,
        private NotificationService $notificationService,
        private LoggerInterface $logger
    ) {}

    /**
     * Vérifier authentification
     */
    private function checkAuth(): ?Response
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->redirectToRoute('admin_login');
        }
        return null;
    }

    /**
     * Page de connexion admin
     */
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        if ($this->authService->isLoggedIn()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');

            if ($this->authService->authenticate($username, $password)) {
                $this->addFlash('success', 'Connexion réussie!');
                return $this->redirectToRoute('admin_dashboard');
            } else {
                $error = 'Identifiants incorrects.';
            }
        }

        return $this->render('admin/login.html.twig', [
            'error' => $error,
        ]);
    }

    /**
     * Tableau de bord principal
     */
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $auth = $this->checkAuth();
        if ($auth) return $auth;

        $candidates = $this->entityManager->getRepository(Candidate::class)
            ->findAllWithLatest();

        $totalCandidates = count($candidates);
        $pendingCount = count(array_filter($candidates, fn($c) => $c->getStatus() === 'pending'));
        $analyzedCount = count(array_filter($candidates, fn($c) => $c->getStatus() === 'analyzed'));

        return $this->render('admin/dashboard.html.twig', [
            'candidates' => $candidates,
            'totalCandidates' => $totalCandidates,
            'pendingCount' => $pendingCount,
            'analyzedCount' => $analyzedCount,
        ]);
    }

    /**
     * Voir détails d'un candidat
     */
    #[Route('/candidate/{id}', name: 'view_candidate', methods: ['GET'])]
    public function viewCandidate(string $id): Response
    {
        $auth = $this->checkAuth();
        if ($auth) return $auth;

        $candidate = $this->entityManager->getRepository(Candidate::class)->find($id);

        if (!$candidate) {
            throw $this->createNotFoundException('Candidat non trouvé');
        }

        return $this->render('admin/view_candidate.html.twig', [
            'candidate' => $candidate,
        ]);
    }

    /**
     * Analyser un candidat
     */
    #[Route('/candidate/{id}/analyze', name: 'analyze_candidate_web', methods: ['POST'])]
    public function analyzeCandidate(string $id, Request $request): Response
    {
        $auth = $this->checkAuth();
        if ($auth) return $auth;

        $candidate = $this->entityManager->getRepository(Candidate::class)->find($id);

        if (!$candidate) {
            $this->addFlash('error', 'Candidat non trouvé');
            return $this->redirectToRoute('admin_dashboard');
        }

        $jobDescription = $request->request->get('jobDescription');

        if (!$jobDescription) {
            $this->addFlash('error', 'Fiche de poste obligatoire');
            return $this->redirectToRoute('admin_view_candidate', ['id' => $id]);
        }

        try {
            $candidate->setStatus('analyzing');
            $this->entityManager->flush();

            $this->logger->info('Analyse lancée', ['candidateId' => $id]);

            $result = $this->geminiService->analyzeCandidate($jobDescription, $candidate->getCvText());

            $candidate->setAnalysisResult($result);
            $candidate->setScore($result['score'] ?? null);
            $candidate->setStatus('analyzed');
            $candidate->setAnalyzedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            // Envoyer notification si score >= 80
            if ($result['score'] ?? 0 >= 80) {
                $this->notificationService->notifyIfQualified($candidate, $result['score']);
            } else {
                $this->notificationService->notifyIfRejected($candidate, $result['score']);
            }

            $this->logger->info('Analyse réussie', ['candidateId' => $id, 'score' => $result['score']]);
            $this->addFlash('success', 'Analyse réussie! Score: ' . ($result['score'] ?? 'N/A'));

            return $this->redirectToRoute('admin_view_candidate', ['id' => $id]);

        } catch (GeminiException $e) {
            $candidate->setStatus('pending');
            $this->entityManager->flush();

            $this->logger->error('Erreur Gemini: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur AI: ' . $e->getMessage());

            return $this->redirectToRoute('admin_view_candidate', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());

            return $this->redirectToRoute('admin_view_candidate', ['id' => $id]);
        }
    }

    /**
     * Supprimer un candidat
     */
    #[Route('/candidate/{id}/delete', name: 'delete_candidate', methods: ['POST'])]
    public function deleteCandidate(string $id): Response
    {
        $auth = $this->checkAuth();
        if ($auth) return $auth;

        $candidate = $this->entityManager->getRepository(Candidate::class)->find($id);

        if (!$candidate) {
            $this->addFlash('error', 'Candidat non trouvé');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            $this->entityManager->remove($candidate);
            $this->entityManager->flush();

            $this->logger->info('Candidat supprimé', ['candidateId' => $id]);
            $this->addFlash('success', 'Candidat supprimé.');

            return $this->redirectToRoute('admin_dashboard');

        } catch (\Exception $e) {
            $this->logger->error('Erreur suppression: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de la suppression');

            return $this->redirectToRoute('admin_dashboard');
        }
    }

    /**
     * Déconnexion
     */
    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(): Response
    {
        $this->authService->logout();
        $this->addFlash('success', 'Déconnecté.');
        return $this->redirectToRoute('app_public_home');
    }
}
