<?php
namespace App\Controller;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NotificationController extends AbstractController
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // Notifications page
    #[Route('/notifications', name: 'notifications_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $notifications = $this->notificationService->getUserNotifications($user->getId());
        
        return $this->render('notifications.html.twig', [
            'notifications' => $notifications
        ]);
    }

    #[Route('/api/notifications/user/{userId}', name: 'api_notifications_by_user', methods: ['GET'])]
    public function getUserNotifications(int $userId): JsonResponse
    {
        $notifications = $this->notificationService->getUserNotifications($userId);
        return $this->json($notifications);
    }

    #[Route('/notifications/mark-read', name: 'notifications_mark_read', methods: ['POST'])]
    public function markAsRead(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('mark_read', $request->request->get('_token'))) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 400);
        }

        $id = $request->request->get('id');
        $success = $this->notificationService->markAsRead($id);
        
        if ($success) {
            return $this->json(['success' => true, 'message' => 'Notification marked as read']);
        }
        
        return $this->json(['success' => false, 'error' => 'Notification not found'], 404);
    }

    #[Route('/api/notifications/{id}/read', name: 'api_notifications_mark_read', methods: ['PATCH'])]
    public function apiMarkAsRead(int $id): JsonResponse
    {
        $success = $this->notificationService->markAsRead($id);
        
        if ($success) {
            return $this->json(['message' => 'Notification marked as read']);
        }
        
        return $this->json(['error' => 'Notification not found'], 404);
    }

    #[Route('/api/notifications', name: 'api_notifications_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);
        
        $userId = $data['userId'] ?? null;
        $message = $data['message'] ?? null;

        if (!$userId || !$message) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        $success = $this->notificationService->createNotification($userId, $message);
        
        if ($success) {
            return $this->json(['message' => 'Notification created successfully']);
        }
        
        return $this->json(['error' => 'Failed to create notification'], 400);
    }

    #[Route('/api/notifications/user/{userId}', name: 'api_notifications_delete_by_user', methods: ['DELETE'])]
    public function deleteUserNotifications(int $userId): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->notificationService->deleteUserNotifications($userId);
        return $this->json(['message' => 'User notifications deleted successfully']);
    }

    #[Route('/api/notifications', name: 'api_notifications_delete_all', methods: ['DELETE'])]
    public function deleteAll(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->notificationService->deleteAllNotifications();
        return $this->json(['message' => 'All notifications deleted successfully']);
    }
}