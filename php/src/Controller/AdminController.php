<?php

namespace App\Controller;

use App\Form\Type\AdminUploadType;
use App\Service\LogService;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class AdminController extends AbstractController
{
    private LogService $logService;
    private TaskService $taskService;

    public function __construct(LogService $logService, TaskService $taskService)
    {
        $this->logService = $logService;
        $this->taskService = $taskService;
    }

    // Function to render the admin page and manage it's components
    #[Route('/admin', name: 'admin_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        // Only admin access
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $stats = $this->taskService->getPendingStats();
        
        // Create upload form
        $uploadForm = $this->createForm(AdminUploadType::class);
        $uploadForm->handleRequest($request);

        // If we are uploading the log file, handle it and reload
        if ($uploadForm->isSubmitted() && $uploadForm->isValid()) {
            $file = $uploadForm->get('logfile')->getData();
            
            if ($file) {
                $user = $this->getUser();
                $userId = $user->getId();
                $username = $user->getUsername();

                $fileArray = [
                    'error' => $file->getError(),
                    'tmp_name' => $file->getPathname(),
                    'name' => $file->getClientOriginalName()
                ];

                try {
                    $result = $this->logService->handleLogUpload($fileArray, $userId, $username);
                    $this->addFlash('success', $result);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Upload failed: ' . $e->getMessage());
                }

                return $this->redirectToRoute('admin_index');
            }
        }
        
        return $this->render('admin.html.twig', [
            'uploadForm' => $uploadForm->createView(),
            'pendingTasks' => $stats['pending'],
            'criticalTasks' => $stats['critical']
        ]);
    }

    // As i changed the other function to handle all, this is just a placeholder for backword compatibility
    #[Route('/admin/upload', name: 'admin_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        return $this->redirectToRoute('admin_index');
    }
}