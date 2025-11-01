<?php

namespace App\Service;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthService
{
    private PasswordHasherFactory $factory;

    public function __construct(
        private AdminRepository $adminRepository,
        private EntityManagerInterface $entityManager,
        private SessionInterface $session
    ) {
        $this->factory = new PasswordHasherFactory([
            'common' => ['algorithm' => 'bcrypt'],
        ]);
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
        $this->session->set('admin_id', $admin->getId());
        $this->session->set('admin_username', $admin->getUsername());

        return true;
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn(): bool
    {
        return $this->session->has('admin_id');
    }

    /**
     * Obtenir l'admin actuellement connecté
     */
    public function getLoggedInAdmin(): ?Admin
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->adminRepository->find($this->session->get('admin_id'));
    }

    /**
     * Déconnexion
     */
    public function logout(): void
    {
        $this->session->remove('admin_id');
        $this->session->remove('admin_username');
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
