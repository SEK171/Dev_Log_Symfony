<?php

namespace App\Controller;

use App\Entity\Log;
use App\Form\Type\LogType;
use App\Form\Type\FilterType;
use App\Form\Type\TaskType;
use App\Service\LogService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LogController extends AbstractController
{
    private LogService $logService;
    private UserService $userService;

    public function __construct(LogService $logService, UserService $userService)
    {
        $this->logService = $logService;
        $this->userService = $userService;
    }

    // The main page render with 2 routes
    #[Route('/', name: 'logs_index', methods: ['GET'])]
    #[Route('/home', name: 'logs_home', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Create filters form
        $filtersForm = $this->createForm(FilterType::class, null, [
            'channels' => $this->logService->getAvailableChannels(),
            'types' => $this->logService->getAvailableTypes(),
        ]);
        
        $filtersForm->handleRequest($request);

        $channel = null;
        $type = null;
        $startDate = null;
        $endDate = null;

        if ($filtersForm->isSubmitted() && $filtersForm->isValid()) {
            $data = $filtersForm->getData();
            $channel = $data['channel'];
            $type = $data['type'];
            $startDate = $data['startDate'] ? $data['startDate']->format('Y-m-d') : null;
            $endDate = $data['endDate'] ? $data['endDate']->format('Y-m-d') : null;
        } else {
            // Handle URL parameters for backward compatibility (change later)
            $channel = $request->query->get('channel');
            $type = $request->query->get('type');
            $startDate = $request->query->get('startDate');
            $endDate = $request->query->get('endDate');
        }

        if ($channel || $type || $startDate || $endDate) {
            $logs = $this->logService->getFilteredLogs($channel, $type, $startDate, $endDate);
        } else {
            $logs = $this->logService->getAllLogs();
        }

        // handle assigning tasks to users
        $users = [];
        $assignTaskForm = null;
        $createForm = null;
        
        if ($this->isGranted('ROLE_ADMIN')) {
            $users = $this->userService->getAssignableUsers();
            
            // Create assign task form
            $assignTaskForm = $this->createForm(TaskType::class, null, [
                'users' => $users,
            ]);

            // Create log form
            $log = new Log();
            $log->setDatetime(new \DateTime());
            $createForm = $this->createForm(LogType::class, $log);
        }

        // and finally render
        return $this->render('home.html.twig', [
            'filtersForm' => $filtersForm->createView(),
            'createForm' => $createForm ? $createForm->createView() : null,
            'assignTaskForm' => $assignTaskForm ? $assignTaskForm->createView() : null,
            'logs' => $logs,
            'channels' => $this->logService->getAvailableChannels(),
            'types' => $this->logService->getAvailableTypes(),
            'users' => $users,
            'selected_channel' => $channel ?? '',
            'selected_type' => $type ?? '',
            'startDate' => $startDate ?? '',
            'endDate' => $endDate ?? ''
        ]);
    }

    // api for the logs
    #[Route('/api/logs', name: 'api_logs_index', methods: ['GET'])]
    public function apiIndex(Request $request): JsonResponse
    {
        $channel = $request->query->get('channel');
        $type = $request->query->get('type');
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');

        if ($channel || $type || $startDate || $endDate) {
            $logs = $this->logService->getFilteredLogs($channel, $type, $startDate, $endDate);
        } else {
            $logs = $this->logService->getAllLogs();
        }

        return $this->json($logs);
    }

    // api for deleting logs
    #[Route('/api/logs/{id}', name: 'api_logs_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->logService->deleteLog($id);
        return $this->json(['message' => 'Log deleted successfully']);
    }

    #[Route('/api/logs', name: 'api_logs_delete_all', methods: ['DELETE'])]
    public function deleteAll(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->logService->deleteAllLogs();
        return $this->json(['message' => 'All logs deleted successfully']);
    }

    // api for updating log type
    #[Route('/api/logs/{id}/type', name: 'api_logs_update_type', methods: ['PATCH'])]
    public function updateType(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);
        $newType = $data['type'] ?? null;

        if (!$newType) {
            return $this->json(['error' => 'Type is required'], 400);
        }

        $success = $this->logService->updateLogType($id, $newType);

        if ($success) {
            return $this->json(['message' => 'Log type updated successfully']);
        }

        return $this->json(['error' => 'Log not found'], 404);
    }

    // Api for uploading a log file
    #[Route('/api/logs/upload', name: 'api_logs_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        $user = $this->getUser();
        $userId = $user ? $user->getId() : 1;
        $username = $user ? $user->getUsername() : 'admin';

        $fileArray = [
            'error' => $file->getError(),
            'tmp_name' => $file->getPathname(),
            'name' => $file->getClientOriginalName()
        ];

        $result = $this->logService->handleLogUpload($fileArray, $userId, $username);
        return $this->json(['message' => $result]);
    }

    #[Route('/api/logs/channels', name: 'api_logs_channels', methods: ['GET'])]
    public function getChannels(): JsonResponse
    {
        $channels = $this->logService->getAvailableChannels();
        return $this->json($channels);
    }

    #[Route('/api/logs/types', name: 'api_logs_types', methods: ['GET'])]
    public function getTypes(): JsonResponse
    {
        $types = $this->logService->getAvailableTypes();
        return $this->json($types);
    }

    // Here we'll deal with importing (seperate the logic from uploading later)
    #[Route('/api/logs/import', name: 'api_logs_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);
        $filePath = $data['filePath'] ?? null;

        if (!$filePath) {
            return $this->json(['error' => 'File path is required'], 400);
        }

        $imported = $this->logService->importLogsFromFile($filePath);
        return $this->json(['message' => "Imported $imported logs successfully"]);
    }
    
    // Function to create a log
    #[Route('/api/logs/create', name: 'api_logs_create', methods: ['POST'])]
    public function apiCreateLog(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $log = new Log();
        $form = $this->createForm(LogType::class, $log);
        
        // Handle form data properly for AJAX requests
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($log);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Log created successfully!']);
        }

        // Collect form errors
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $fieldName = $error->getOrigin() ? $error->getOrigin()->getName() : 'form';
            $errors[$fieldName] = $error->getMessage();
        }
        
        return $this->json(['success' => false, 'errors' => $errors], 400);
    }
}