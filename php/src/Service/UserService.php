<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    public function createUser(string $username, string $password, int $permission, int $creatorPermission): bool
    {
        if ($creatorPermission <= $permission) {
            return false;
        }

        $existing = $this->userRepository->findByUsername($username);
        if ($existing !== null) {
            return false;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($password);
        $user->setPermission($permission);
        $user->setRegistered(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return true;
    }

    public function deleteUser(int $userId, int $permission, int $requestPermission): bool
    {
        if ($requestPermission <= $permission) {
            return false;
        }

        $user = $this->userRepository->find($userId);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function getUsersWithPermission(int $permission): array
    {
        return $this->userRepository->findByPermission($permission);
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function getAssignableUsers(): array
    {
        return $this->userRepository->findByLowerPermission(0);
    }

    public function getUserByUsername(string $username): ?User
    {
        return $this->userRepository->findByUsername($username);
    }

    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }
}
