<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use App\Entity\Candidate;
use App\Entity\JobDescription;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CandidateAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Page d'inscription candidat
     */
    #[Route('/register', name: 'app_candidate_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');
            $email = $request->request->get('email');
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirmPassword');

            // Validations
            $errors = [];

            if (!$firstName || strlen($firstName) < 2) {
                $errors[] = 'Le prénom est obligatoire (min 2 caractères)';
            }
            if (!$lastName || strlen($lastName) < 2) {
                $errors[] = 'Le nom est obligatoire (min 2 caractères)';
            }
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email invalide';
            }
            if (!$username || strlen($username) < 3) {
                $errors[] = 'Identifiant obligatoire (min 3 caractères)';
            }
            if (!$password || strlen($password) < 6) {
                $errors[] = 'Mot de passe obligatoire (min 6 caractères)';
            }
            if ($password !== $confirmPassword) {
                $errors[] = 'Les mots de passe ne correspondent pas';
            }

            // Vérifier unicité
            $existing = $this->entityManager->getRepository(Candidate::class)
                ->findOneBy(['email' => $email]);
            if ($existing) {
                $errors[] = 'Cet email est déjà utilisé';
            }

            $existing = $this->entityManager->getRepository(Candidate::class)
                ->findOneBy(['username' => $username]);
            if ($existing) {
                $errors[] = 'Cet identifiant est déjà utilisé';
            }

            if (empty($errors)) {
                try {
                    $candidate = new Candidate();
                    $candidate->setFirstName($firstName);
                    $candidate->setLastName($lastName);
                    $candidate->setEmail($email);
                    $candidate->setUsername($username);

                    // Hash password
                    $factory = new PasswordHasherFactory(['common' => ['algorithm' => 'bcrypt']]);
                    $hasher = $factory->getPasswordHasher('common');
                    $hashedPassword = $hasher->hash($password);
                    $candidate->setPassword($hashedPassword);

                    $this->entityManager->persist($candidate);
                    $this->entityManager->flush();

                    $this->logger->info('Nouveau candidat inscrit', ['email' => $email]);

                    // Se connecter automatiquement
                    $session = $request->getSession();
                    $session->set('candidate_id', $candidate->getId());
                    $session->set('candidate_username', $candidate->getUsername());

                    $this->addFlash('success', 'Compte créé! Vous êtes connecté.');
                    return $this->redirectToRoute('app_candidate_dashboard');

                } catch (\Exception $e) {
                    $this->logger->error('Erreur inscription: ' . $e->getMessage());
                    $this->addFlash('error', 'Erreur lors de l\'inscription');
                }
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        return $this->render('candidate/register.html.twig');
    }

    /**
     * Page de connexion candidat
     */
    #[Route('/login', name: 'app_candidate_login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        // Si déjà connecté
        if ($request->getSession()->has('candidate_id')) {
            return $this->redirectToRoute('app_candidate_dashboard');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');

            $candidate = $this->entityManager->getRepository(Candidate::class)
                ->findByUsername($username);

            if (!$candidate) {
                $error = 'Identifiant ou mot de passe incorrect';
            } else {
                $factory = new PasswordHasherFactory(['common' => ['algorithm' => 'bcrypt']]);
                $hasher = $factory->getPasswordHasher('common');

                if (!$hasher->verify($candidate->getPassword(), $password)) {
                    $error = 'Identifiant ou mot de passe incorrect';
                } else {
                    // Connexion réussie
                    $session = $request->getSession();
                    $session->set('candidate_id', $candidate->getId());
                    $session->set('candidate_username', $candidate->getUsername());

                    $this->logger->info('Candidat connecté', ['username' => $username]);

                    $this->addFlash('success', 'Bienvenue!');
                    return $this->redirectToRoute('app_candidate_dashboard');
                }
            }
        }

        return $this->render('candidate/login.html.twig', [
            'error' => $error,
        ]);
    }

    /**
     * Tableau de bord candidat
     */
    #[Route('/dashboard', name: 'app_candidate_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->has('candidate_id')) {
            return $this->redirectToRoute('app_candidate_login');
        }

        $candidate = $this->entityManager->getRepository(Candidate::class)
            ->find($session->get('candidate_id'));

        if (!$candidate) {
            $session->remove('candidate_id');
            return $this->redirectToRoute('app_candidate_login');
        }

        $notifications = $candidate->getNotifications();

        return $this->render('candidate/dashboard.html.twig', [
            'candidate' => $candidate,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get available job offers
     */
    #[Route('/get-jobs', name: 'app_candidate_get_jobs', methods: ['GET'])]
    public function getJobs(): Response
    {
        $jobs = $this->entityManager->getRepository(JobDescription::class)
            ->findBy(['isActive' => true]);

        $data = array_map(fn($job) => [
            'id' => $job->getId(),
            'title' => $job->getTitle(),
            'description' => $job->getDescription(),
        ], $jobs);

        return $this->json($data);
    }

    /**
     * Select a job offer to apply to
     */
    #[Route('/select-job', name: 'app_candidate_select_job', methods: ['POST'])]
    public function selectJob(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->has('candidate_id')) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $jobId = $data['jobId'] ?? null;

        if (!$jobId) {
            return $this->json(['success' => false, 'message' => 'Offre requise'], 400);
        }

        $job = $this->entityManager->getRepository(JobDescription::class)->find($jobId);
        if (!$job || !$job->isActive()) {
            return $this->json(['success' => false, 'message' => 'Offre non trouvée'], 404);
        }

        $candidate = $this->entityManager->getRepository(Candidate::class)
            ->find($session->get('candidate_id'));

        if (!$candidate) {
            return $this->json(['success' => false, 'message' => 'Candidat non trouvé'], 404);
        }

        try {
            $candidate->setJobDescription($job);
            $this->entityManager->flush();

            $this->logger->info('Offre sélectionnée', [
                'candidateId' => $candidate->getId(),
                'jobId' => $jobId,
                'jobTitle' => $job->getTitle()
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Offre sélectionnée avec succès'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur sélection offre: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Upload CV PDF
     */
    #[Route('/upload-cv', name: 'app_candidate_upload_cv', methods: ['POST'])]
    public function uploadCv(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->has('candidate_id')) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $candidateId = $session->get('candidate_id');
        $candidate = $this->entityManager->getRepository(Candidate::class)->find($candidateId);

        if (!$candidate) {
            return $this->json(['success' => false, 'message' => 'Candidat non trouvé'], 404);
        }

        try {
            $cvFile = $request->files->get('cvFile');

            if (!$cvFile) {
                return $this->json(['success' => false, 'message' => 'Aucun fichier sélectionné'], 400);
            }

            if ($cvFile->getMimeType() !== 'application/pdf') {
                return $this->json(['success' => false, 'message' => 'Le fichier doit être au format PDF'], 400);
            }

            if ($cvFile->getSize() > 5 * 1024 * 1024) {
                return $this->json(['success' => false, 'message' => 'Le fichier ne doit pas dépasser 5MB'], 400);
            }

            // Create uploads directory if not exists
            // Use var/uploads instead of public/uploads for better permission handling
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/var/uploads/cv';
            if (!is_dir($uploadsDir)) {
                try {
                    mkdir($uploadsDir, 0777, true);
                } catch (\Exception $e) {
                    $this->logger->error('Could not create uploads directory: ' . $e->getMessage());
                    return $this->json(['success' => false, 'message' => 'Erreur serveur: impossible de créer le répertoire'], 500);
                }
            }

            // Generate unique filename
            $originalFileName = $cvFile->getClientOriginalName();
            $fileName = $candidate->getId() . '_' . time() . '_' . pathinfo($originalFileName, PATHINFO_FILENAME) . '.pdf';

            // Move file
            $cvFile->move($uploadsDir, $fileName);

            // Update candidate
            $candidate->setCvFileName($fileName);
            $candidate->setStatus('pending');

            $this->entityManager->persist($candidate);
            $this->entityManager->flush();

            $this->logger->info('CV téléchargé', ['candidate_id' => $candidateId, 'filename' => $fileName]);

            return $this->json([
                'success' => true,
                'message' => 'CV téléchargé avec succès! Votre candidature va être analysée.'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur upload CV: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erreur lors du téléchargement'], 500);
        }
    }

    /**
     * Serve CV PDF for viewing (accessible from admin and candidate)
     */
    #[Route('/cv/{id}', name: 'app_candidate_view_cv', methods: ['GET'])]
    public function viewCv(string $id, Request $request): Response
    {
        $session = $request->getSession();
        $isAdmin = $session->has('admin_id');
        $isCandidate = $session->has('candidate_id') && $session->get('candidate_id') === $id;

        // Only admin or the candidate themselves can view
        if (!$isAdmin && !$isCandidate) {
            return new Response('Unauthorized', 403);
        }

        $candidate = $this->entityManager->getRepository(Candidate::class)->find($id);

        if (!$candidate || !$candidate->getCvFileName()) {
            return new Response('CV not found', 404);
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/var/uploads/cv';
        $filePath = $uploadsDir . '/' . $candidate->getCvFileName();

        if (!file_exists($filePath)) {
            return new Response('File not found', 404);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(\Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_INLINE, $candidate->getCvFileName());
        return $response;
    }

    /**
     * Download CV PDF
     */
    #[Route('/download-cv/{id}', name: 'app_candidate_download_cv', methods: ['GET'])]
    public function downloadCv(string $id, Request $request): Response
    {
        $session = $request->getSession();
        $isAdmin = $session->has('admin_id');
        $isCandidate = $session->has('candidate_id') && $session->get('candidate_id') === $id;

        // Only admin or the candidate themselves can download
        if (!$isAdmin && !$isCandidate) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_public_home');
        }

        $candidate = $this->entityManager->getRepository(Candidate::class)->find($id);

        if (!$candidate || !$candidate->getCvFileName()) {
            $this->addFlash('error', 'CV non trouvé');
            if ($isCandidate) {
                return $this->redirectToRoute('app_candidate_dashboard');
            }
            return $this->redirectToRoute('admin_dashboard');
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/var/uploads/cv';
        $filePath = $uploadsDir . '/' . $candidate->getCvFileName();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Fichier non trouvé');
            if ($isCandidate) {
                return $this->redirectToRoute('app_candidate_dashboard');
            }
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->file($filePath);
    }

    /**
     * Déconnexion
     */
    #[Route('/logout', name: 'app_candidate_logout', methods: ['GET'])]
    public function logout(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('candidate_id');
        $session->remove('candidate_username');

        $this->addFlash('success', 'Déconnecté');
        return $this->redirectToRoute('app_public_home');
    }
}
