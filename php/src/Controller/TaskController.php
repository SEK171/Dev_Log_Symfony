<?php

namespace App\Controller;

use App\Form\Type\TaskType;
use App\Service\TaskService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    private TaskService $taskService;
    private UserService $userService;

    public function __construct(TaskService $taskService, UserService $userService)
    {
        $this->taskService = $taskService;
        $this->userService = $userService;
    }

    // tasks page
    #[Route('/tasks', name: 'tasks_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $userId = $user->getId();
        $permission = $user->getPermission();

        $tasks = $this->taskService->getVisibleTasks($userId, $permission);
        
        return $this->render('tasks.html.twig', [
            'tasks' => $tasks
        ]);
    }

    #[Route('/api/tasks', name: 'api_tasks_index', methods: ['GET'])]
    public function apiIndex(): JsonResponse
    {
        $user = $this->getUser();
        $userId = $user->getId();
        $permission = $user->getPermission();

        $tasks = $this->taskService->getVisibleTasks($userId, $permission);
        return $this->json($tasks);
    }

    // function to assign a task to a user of lower permission level
    #[Route('/tasks/assign', name: 'task_assign', methods: ['POST'])]
    public function assign(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $users = $this->userService->getAssignableUsers();
        $assignTaskForm = $this->createForm(TaskType::class, null, [
            'users' => $users,
        ]);
        
        $assignTaskForm->handleRequest($request);
        
        if ($assignTaskForm->isSubmitted() && $assignTaskForm->isValid()) {
            $data = $assignTaskForm->getData();
            
            $logId = $data['log_id'];
            $assignedToId = $data['assigned_to'];
            $description = $data['description'];
            
            $user = $this->getUser();
            $assignedById = $user->getId();

            $success = $this->taskService->assignTask($logId, $assignedById, $assignedToId, $description);
            
            if ($success) {
                return $this->json(['success' => true, 'message' => 'Task assigned successfully']);
            }
            
            return $this->json(['success' => false, 'error' => 'Failed to assign task'], 400);
        }
        
        // Handle form errors
        $errors = [];
        foreach ($assignTaskForm->getErrors(true) as $error) {
            $fieldName = $error->getOrigin() ? $error->getOrigin()->getName() : 'form';
            $errors[$fieldName] = $error->getMessage();
        }
        
        return $this->json(['success' => false, 'errors' => $errors], 400);
    }

    #[Route('/tasks/update', name: 'task_update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $taskId = $request->request->get('task_id');
        $newStatus = $request->request->get('status');
        
        $user = $this->getUser();
        $userId = $user->getId();
        $username = $user->getUsername();

        if (!$taskId || !$newStatus) {
            $this->addFlash('error', 'Missing required fields');
            return $this->redirectToRoute('tasks_index');
        }

        $success = $this->taskService->updateTaskStatus($taskId, $newStatus, $userId, $username);
        
        if ($success) {
            $this->addFlash('success', 'Task status updated successfully');
        } else {
            $this->addFlash('error', 'Task not found');
        }
        
        return $this->redirectToRoute('tasks_index');
    }

    #[Route('/api/tasks/{id}/status', name: 'api_tasks_update_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;
        
        $user = $this->getUser();
        $userId = $user->getId();
        $username = $user->getUsername();

        if (!$newStatus) {
            return $this->json(['error' => 'Status is required'], 400);
        }

        $success = $this->taskService->updateTaskStatus($id, $newStatus, $userId, $username);
        
        if ($success) {
            return $this->json(['message' => 'Task status updated successfully']);
        }
        
        return $this->json(['error' => 'Task not found'], 404);
    }

    #[Route('/api/tasks/{id}', name: 'api_tasks_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->taskService->deleteTask($id);
        return $this->json(['message' => 'Task deleted successfully']);
    }

    #[Route('/api/tasks/user/{userId}', name: 'api_tasks_by_user', methods: ['GET'])]
    public function getUserTasks(int $userId): JsonResponse
    {
        $tasks = $this->taskService->getUserTasks($userId);
        return $this->json($tasks);
    }

    #[Route('/api/tasks/stats', name: 'api_tasks_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $stats = $this->taskService->getPendingStats();
        return $this->json($stats);
    }

    #[Route('/api/tasks', name: 'api_tasks_delete_all', methods: ['DELETE'])]
    public function deleteAll(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->taskService->deleteAllTasks();
        return $this->json(['message' => 'All tasks deleted successfully']);
    }
}