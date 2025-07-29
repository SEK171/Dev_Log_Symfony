<?php
namespace App\Service;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private NotificationRepository $notificationRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        NotificationRepository $notificationRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->notificationRepository = $notificationRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    public function getUserNotifications(int $userId): array
    {
        return $this->notificationRepository->findByUser($userId);
    }

    public function markAsRead(int $notificationId): bool
    {
        return $this->notificationRepository->markAsRead($notificationId);
    }

    public function createNotification(int $userId, string $message): bool
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return false;
        }

        $notification = new Notification();
        $notification->setUser($user);
        $notification->setMessage($message);
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTime());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return true;
    }

    public function deleteUserNotifications(int $userId): void
    {
        $this->notificationRepository->deleteByUser($userId);
    }

    public function deleteAllNotifications(): void
    {
        $this->notificationRepository->deleteAll();
    }
}