<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Candidate;
use App\Entity\JobDescription;
use App\Service\AuthService;
use Doctrine\Common\Collections\Collection;
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
     * Tableau de bord principal - 2 vues: offres -> candidats
     */
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        $auth = $this->checkAuth();
        if ($auth) return $auth;

        $jobId = $request->query->get('job');
        $filter = $request->query->get('filter', 'all');

        // Get all active jobs
        $jobs = $this->entityManager->getRepository(JobDescription::class)
            ->findBy(['isActive' => true]);

        // Calculate stats for all jobs
        $jobStats = [];
        foreach ($jobs as $job) {
            $allJobCandidates = $this->entityManager->getRepository(Candidate::class)
                ->findBy(['jobDescription' => $job]);

            $jobStats[$job->getId()] = [
                'total' => count($allJobCandidates),
                'pending' => count(array_filter($allJobCandidates, fn($c) => $c->getStatus() === 'pending')),
                'analyzed' => count(array_filter($allJobCandidates, fn($c) => $c->getStatus() === 'analyzed')),
            ];
        }

        // Get totals for all candidates
        $allCandidates = $this->entityManager->getRepository(Candidate::class)->findAll();
        $totalCandidates = count($allCandidates);
        $totalAnalyzed = count(array_filter($allCandidates, fn($c) => $c->getStatus() === 'analyzed'));
        $totalPending = count(array_filter($allCandidates, fn($c) => $c->getStatus() === 'pending'));

        $candidatesByJob = [];
        $currentJob = null;
        $jobPendingCount = 0;
        $jobAnalyzedCount = 0;
        $totalJobCandidates = 0;

        // If a job is selected, get its candidates
        if ($jobId) {
            $currentJob = $this->entityManager->getRepository(JobDescription::class)->find($jobId);

            if ($currentJob) {
                $candidates = $this->entityManager->getRepository(Candidate::class)
                    ->findBy(['jobDescription' => $currentJob]);

                // Count stats before filtering
                $jobPendingCount = count(array_filter($candidates, fn($c) => $c->getStatus() === 'pending'));
                $jobAnalyzedCount = count(array_filter($candidates, fn($c) => $c->getStatus() === 'analyzed'));
                $totalJobCandidates = count($candidates);

                // Apply filter
                if ($filter === 'analyzed') {
                    $candidates = array_filter($candidates, fn($c) => $c->getStatus() === 'analyzed');
                } elseif ($filter === 'pending') {
                    $candidates = array_filter($candidates, fn($c) => $c->getStatus() === 'pending');
                }

                $candidatesByJob = array_values($candidates);
            }
        }

        return $this->render('admin/dashboard.html.twig', [
            'jobs' => $jobs,
            'jobStats' => $jobStats,
            'candidatesByJob' => $candidatesByJob,
            'currentJob' => $currentJob,
            'selectedJobId' => $jobId,
            'currentFilter' => $filter,
            'totalCandidates' => $totalCandidates,
            'totalAnalyzed' => $totalAnalyzed,
            'totalPending' => $totalPending,
            'jobPendingCount' => $jobPendingCount,
            'jobAnalyzedCount' => $jobAnalyzedCount,
            'totalJobCandidates' => $totalJobCandidates,
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
     * Analyser un candidat - utilise la job description déjà liée
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

        if (!$candidate->getJobDescription()) {
            $this->addFlash('error', 'Aucune offre liée à ce candidat');
            return $this->redirectToRoute('admin_view_candidate', ['id' => $id]);
        }

        try {
            $candidate->setStatus('analyzing');
            $this->entityManager->flush();

            $jobDescription = $candidate->getJobDescription()->getDescription();
            $cvText = $candidate->getCvText() ?? '';

            $this->logger->info('Analyse lancée', ['candidateId' => $id]);

            $result = $this->geminiService->analyzeCandidate($jobDescription, $cvText);

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
     * Analyser tous les candidats d'une offre (batch)
     */
    #[Route('/job/{jobId}/analyze-batch', name: 'analyze_batch', methods: ['POST'])]
    public function analyzeBatch(int $jobId): Response
    {
        $auth = $this->checkAuth();
        if ($auth) return $auth;

        $job = $this->entityManager->getRepository(JobDescription::class)->find($jobId);

        if (!$job) {
            $this->addFlash('error', 'Offre non trouvée');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Get all pending candidates for this job
        $candidates = $this->entityManager->getRepository(Candidate::class)
            ->findBy(['jobDescription' => $job, 'status' => 'pending']);

        $analyzed = 0;
        $failed = 0;

        foreach ($candidates as $candidate) {
            try {
                $candidate->setStatus('analyzing');
                $this->entityManager->flush();

                $jobDescription = $job->getDescription();
                $cvText = $candidate->getCvText() ?? '';

                $result = $this->geminiService->analyzeCandidate($jobDescription, $cvText);

                $candidate->setAnalysisResult($result);
                $candidate->setScore($result['score'] ?? null);
                $candidate->setStatus('analyzed');
                $candidate->setAnalyzedAt(new \DateTimeImmutable());
                $this->entityManager->flush();

                // Send notifications
                if ($result['score'] ?? 0 >= 80) {
                    $this->notificationService->notifyIfQualified($candidate, $result['score']);
                } else {
                    $this->notificationService->notifyIfRejected($candidate, $result['score']);
                }

                $analyzed++;
                $this->logger->info('Analyse batch réussie', ['candidateId' => $candidate->getId(), 'score' => $result['score']]);

            } catch (\Exception $e) {
                $candidate->setStatus('pending');
                $this->entityManager->flush();
                $failed++;
                $this->logger->error('Erreur analyse batch: ' . $e->getMessage());
            }
        }

        $message = "Analyse terminée: $analyzed candidats analysés";
        if ($failed > 0) {
            $message .= ", $failed erreurs";
        }

        $this->addFlash('success', $message);
        return $this->redirectToRoute('admin_dashboard', ['filter' => 'pending']);
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
