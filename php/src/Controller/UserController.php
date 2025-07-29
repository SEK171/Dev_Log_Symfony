<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // no users page for now just requests
    #[Route('', name: 'users_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();
        return $this->json($users);
    }

    #[Route('', name: 'users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        $permission = $data['permission'] ?? null;
        $currentUser = $this->getUser();
        $creatorPermission = $currentUser ? $currentUser->getPermission() : null;

        if (!$username || !$password || $permission === null) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        $success = $this->userService->createUser($username, $password, $permission, $creatorPermission);

        if ($success) {
            return $this->json(['message' => 'User created successfully']);
        }

        return $this->json(['error' => 'Failed to create user (already exists or permission denied)'], 400);
    }

    #[Route('/{id}', name: 'users_delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $userPermission = $this->userService->getUserById($id)->getPermission();
        $requestPermission = $this->getUser()->getPermission();

        $success = $this->userService->deleteUser($id, $userPermission, $requestPermission);

        if ($success) {
            return $this->json(['message' => 'User deleted successfully']);
        }

        return $this->json(['error' => 'Permission denied or user not found'], 403);
    }

    #[Route('/permission/{permission}', name: 'users_by_permission', methods: ['GET'])]
    public function getUsersByPermission(int $permission): JsonResponse
    {
        $users = $this->userService->getUsersWithPermission($permission);
        return $this->json($users);
    }

    #[Route('/assignable', name: 'users_assignable', methods: ['GET'])]
    public function getAssignableUsers(): JsonResponse
    {
        $users = $this->userService->getAssignableUsers();
        return $this->json($users);
    }

    #[Route('/username/{username}', name: 'users_by_username', methods: ['GET'])]
    public function getUserByUsername(string $username): JsonResponse
    {
        $user = $this->userService->getUserByUsername($username);

        if ($user) {
            return $this->json($user);
        }

        return $this->json(['error' => 'User not found'], 404);
    }
}
