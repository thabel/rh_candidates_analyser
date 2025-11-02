<?php

namespace App\Command;

use App\Entity\JobDescription;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-data',
    description: 'Seed the database with initial data (admin user and job offer)'
)]
class SeedDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuthService $authService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Créer l'admin par défaut
            $io->section('Creating default admin user...');
            $admin = $this->authService->createDefaultAdmin();
            $io->success("✓ Admin created: admin / admin123");

            // Créer l'offre d'emploi par défaut
            $io->section('Creating job offer...');

            $existingJob = $this->entityManager->getRepository(JobDescription::class)
                ->findActiveJob();

            if ($existingJob) {
                $io->info('Job offer already exists');
            } else {
                $jobDescription = new JobDescription();
                $jobDescription->setTitle('Senior Full Stack Developer');
                $jobDescription->setDescription(
                    "TechCorp est une entreprise leader en solutions cloud et d'automatisation intelligente. "
                    . "Depuis 2015, nous aidons les plus grandes entreprises européennes à transformer leur infrastructure numérique.\n\n"
                    . "Nous recherchons un Senior Full Stack Developer pour rejoindre notre équipe Core Platform. "
                    . "Vous serez responsable de l'architecture et du développement de nos systèmes critiques, "
                    . "travaillant avec une stack moderne et une équipe de 12 développeurs talentueux.\n\n"
                    . "Responsabilités Clés:\n"
                    . "- Concevoir et développer des APIs RESTful scalables et performantes\n"
                    . "- Développer des interfaces web modernes avec React/Vue.js\n"
                    . "- Optimiser les performances des bases de données\n"
                    . "- Mentorer les développeurs juniors\n"
                    . "- Assurer la qualité du code via des code reviews\n\n"
                    . "Profil Recherché:\n"
                    . "- 5+ ans d'expérience en développement full stack\n"
                    . "- Maîtrise de PHP/Laravel ou Python/Django (backend)\n"
                    . "- Solides connaissances en JavaScript/TypeScript\n"
                    . "- Expérience avec les bases de données relationnelles\n"
                    . "- Familiarité avec Docker et les déploiements en production\n\n"
                    . "Ce que nous offrons:\n"
                    . "- Équipement de dernière génération\n"
                    . "- Télétravail 2-3 jours/semaine\n"
                    . "- Budget formation annuel €2,000\n"
                    . "- Tickets restaurant, mutuelle, tickets transport\n"
                    . "- Événements d'équipe et séminaires annuels\n\n"
                    . "Localisation: Paris, France (Hybrid)\n"
                    . "Salaire: €55,000 - €75,000/an\n"
                    . "Type: CDI Temps Plein"
                );
                $jobDescription->setIsActive(true);

                $this->entityManager->persist($jobDescription);
                $this->entityManager->flush();

                $io->success('✓ Job offer created: Senior Full Stack Developer');
            }

            $io->newLine();
            $io->section('Database seeding completed!');
            $io->info([
                '✓ Admin credentials: admin / admin123',
                '✓ Job offer: Senior Full Stack Developer',
                '✓ All set! Start the application and visit http://localhost:8080'
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
