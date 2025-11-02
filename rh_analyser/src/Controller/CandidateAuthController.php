<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use App\Entity\Candidate;
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
            $cvText = $request->request->get('cvText');

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
            if (!$cvText || strlen($cvText) < 100) {
                $errors[] = 'CV obligatoire (min 100 caractères)';
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
                    $candidate->setCvText($cvText);
                    $candidate->setCvFileName('cv.txt');

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
