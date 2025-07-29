<?php
namespace App\Service;

use App\Entity\Task;
use App\Entity\Log;
use App\Repository\TaskRepository;
use App\Repository\LogRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class TaskService
{
    private TaskRepository $taskRepository;
    private LogRepository $logRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private NotificationService $notificationService;

    public function __construct(
        TaskRepository $taskRepository,
        LogRepository $logRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ) {
        $this->taskRepository = $taskRepository;
        $this->logRepository = $logRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->notificationService = $notificationService;
    }

    public function assignTask(int $logId, int $assignedById, int $assignedToId, string $description): bool
    {
        $log = $this->logRepository->find($logId);
        $assignedBy = $this->userRepository->find($assignedById);
        $assignedTo = $this->userRepository->find($assignedToId);

        if (!$log || !$assignedBy || !$assignedTo) {
            return false;
        }

        $task = new Task();
        $task->setLog($log);
        $task->setAssignedBy($assignedBy);
        $task->setAssignedTo($assignedTo);
        $task->setDescription($description);
        $task->setStatus('pending');
        $task->setCreatedAt(new \DateTime());

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $message = "New task assigned: " . substr($description, 0, 50) . "...";
        $this->notificationService->createNotification($assignedToId, $message);

        return true;
    }

    public function updateTaskStatus(int $taskId, string $newStatus, int $userId, string $username): bool
    {
        $task = $this->taskRepository->find($taskId);
        if (!$task) {
            return false;
        }

        $task->setStatus($newStatus);
        $task->setUpdatedAt(new \DateTime());

        if ($newStatus === 'completed') {
            $log = $task->getLog();
            $log->setType('COMPLETED');
            $this->entityManager->persist($log);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $message = "Task #{$taskId} marked as {$newStatus} by {$username}";
        $this->notificationService->createNotification($task->getAssignedBy()->getId(), $message);

        return true;
    }

    public function getUserTasks(int $userId): array
    {
        return $this->taskRepository->findByAssignedTo($userId);
    }

    public function getAllTasks(): array
    {
        return $this->taskRepository->findAll();
    }

    public function deleteTask(int $taskId): void
    {
        $task = $this->taskRepository->find($taskId);
        if ($task) {
            $this->entityManager->remove($task);
            $this->entityManager->flush();
        }
    }

    public function deleteUserTasks(int $userId): void
    {
        $this->taskRepository->deleteByAssignedTo($userId);
    }

    public function deleteAllTasks(): void
    {
        $this->taskRepository->deleteAll();
    }

    public function getPendingStats(): array
    {
        return $this->taskRepository->getPendingStats();
    }

    public function getVisibleTasks(int $userId, int $permission): array
    {
        if ($permission === 0) {
            return $this->taskRepository->findAllWithDetails();
        }
        return $this->taskRepository->findByAssignedToWithDetails($userId);
    }
}