<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Candidate;
use App\Entity\JobDescription;
use App\Repository\JobDescriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;

class PublicCandidateController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JobDescriptionRepository $jobRepository,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * Page d'accueil publique - Affiche la fiche de poste et le formulaire de candidature
     */
    #[Route('/', name: 'app_public_home', methods: ['GET'])]
    public function index(): Response
    {
        $job = $this->jobRepository->findActiveJob();

        if (!$job) {
            $this->addFlash('warning', 'Aucune offre d\'emploi disponible pour le moment.');
            return $this->render('public/no_job.html.twig');
        }

        return $this->render('public/index.html.twig', [
            'job' => $job,
        ]);
    }


    #[Route('/test', name: 'app_public_test', methods: ['GET'])]
    public function test(): Response
    {
        
        //  Load environment variable example
        $text = getenv('TEST_ENV_VARIABLE') ?: 'Variable non définie';

        return $this->render('public/test.html.twig', [
           
            'text' => $text,
        ]);
    }

    /**
     * Soumettre une candidature (formulaire web)
     */
    #[Route('/submit', name: 'app_submit_web', methods: ['POST'])]
    public function submitCandidate(Request $request): Response
    {
        $job = $this->jobRepository->findActiveJob();

        if (!$job) {
            $this->addFlash('error', 'Aucune offre d\'emploi disponible.');
            return $this->redirectToRoute('app_public_home');
        }

        $firstName = $request->request->get('firstName');
        $lastName = $request->request->get('lastName');
        $email = $request->request->get('email');
        $cvText = $request->request->get('cvText');

        // Vérifier les champs
        if (!$firstName || !$lastName || !$email || !$cvText) {
            $this->addFlash('error', 'Tous les champs sont obligatoires.');
            return $this->redirectToRoute('app_public_home');
        }

        // Valider l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Email invalide.');
            return $this->redirectToRoute('app_public_home');
        }

        // Vérifier si l'email existe déjà
        $existing = $this->entityManager->getRepository(Candidate::class)
            ->findByEmail($email);

        if ($existing) {
            $this->addFlash('error', 'Cet email a déjà été utilisé pour candidater. Vous ne pouvez candidater qu\'une seule fois.');
            return $this->redirectToRoute('app_public_home');
        }

        // Vérifier longueur CV
        if (strlen($cvText) < 100) {
            $this->addFlash('error', 'Le CV doit contenir au moins 100 caractères.');
            return $this->redirectToRoute('app_public_home');
        }

        // Créer la candidature
        try {
            $candidate = new Candidate();
            $candidate->setFirstName($firstName);
            $candidate->setLastName($lastName);
            $candidate->setEmail($email);
            $candidate->setCvText($cvText);
            $candidate->setCvFileName('cv.txt');

            $this->entityManager->persist($candidate);
            $this->entityManager->flush();

            $this->logger->info('Candidature web soumise', [
                'candidateId' => $candidate->getId(),
                'email' => $email
            ]);

            $this->addFlash('success', 'Candidature soumise avec succès! Vous recevrez bientôt une réponse.');
            return $this->redirectToRoute('app_public_home');

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la soumission: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de la soumission. Veuillez réessayer.');
            return $this->redirectToRoute('app_public_home');
        }
    }

    /**
     * Vérifier le statut de la candidature
     */
    #[Route('/check-status', name: 'app_check_status', methods: ['GET', 'POST'])]
    public function checkStatus(Request $request): Response
    {
        $candidate = null;
        $email = $request->request->get('email') ?? '';

        if ($request->isMethod('POST') && $email) {
            $candidate = $this->entityManager->getRepository(Candidate::class)
                ->findByEmail($email);

            if (!$candidate) {
                $this->addFlash('warning', 'Candidature non trouvée.');
            }
        }

        return $this->render('public/check_status.html.twig', [
            'candidate' => $candidate,
            'email' => $email,
        ]);
    }
}
