<?php

namespace App\Service;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthService
{
    private PasswordHasherFactory $factory;

    public function __construct(
        private AdminRepository $adminRepository,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {
        $this->factory = new PasswordHasherFactory([
            'common' => ['algorithm' => 'bcrypt'],
        ]);
    }

    /**
     * Obtenir la session courante
     */
    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    /**
     * Vérifier les identifiants admin
     */
    public function authenticate(string $username, string $password): bool
    {
        $admin = $this->adminRepository->findByUsername($username);

        if (!$admin || !$admin->isActive()) {
            return false;
        }

        $hasher = $this->factory->getPasswordHasher('common');
        if (!$hasher->verify($admin->getPassword(), $password)) {
            return false;
        }

        // Stocker dans la session
        $session = $this->getSession();
        $session->set('admin_id', $admin->getId());
        $session->set('admin_username', $admin->getUsername());

        return true;
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn(): bool
    {
        $session = $this->getSession();
        return $session->has('admin_id');
    }

    /**
     * Obtenir l'admin actuellement connecté
     */
    public function getLoggedInAdmin(): ?Admin
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $session = $this->getSession();
        return $this->adminRepository->find($session->get('admin_id'));
    }

    /**
     * Déconnexion
     */
    public function logout(): void
    {
        $session = $this->getSession();
        $session->remove('admin_id');
        $session->remove('admin_username');
    }

    /**
     * Créer l'admin par défaut (admin/admin123)
     */
    public function createDefaultAdmin(): Admin
    {
        $existing = $this->adminRepository->findByUsername('admin');
        if ($existing) {
            return $existing;
        }

        $admin = new Admin();
        $admin->setUsername('admin');
        $admin->setEmail('admin@recruitment.local');
        
        $hasher = $this->factory->getPasswordHasher('common');
        $hashedPassword = $hasher->hash('admin123');
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        return $admin;
    }
}
