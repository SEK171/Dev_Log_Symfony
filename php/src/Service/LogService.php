<?php
namespace App\Service;

use App\Entity\Log;
use App\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;

class LogService
{
    private LogRepository $logRepository;
    private EntityManagerInterface $entityManager;
    private NotificationService $notificationService;

    public function __construct(
        LogRepository $logRepository, 
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ) {
        $this->logRepository = $logRepository;
        $this->entityManager = $entityManager;
        $this->notificationService = $notificationService;
    }

    public function importLogsFromFile(string $filePath): int
    {
        if (!file_exists($filePath)) {
            return 0;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $imported = 0;

        foreach ($lines as $line) {
            $parsed = $this->parseLogLine($line);
            if ($parsed !== null) {
                $log = new Log();
                $log->setDatetime($parsed['datetime']);
                $log->setChannel($parsed['channel']);
                $log->setType($parsed['type']);
                $log->setDescription($parsed['description']);

                $this->entityManager->persist($log);
                $imported++;
            }
        }

        $this->entityManager->flush();
        return $imported;
    }

    public function deleteAllLogs(): void
    {
        $this->logRepository->deleteAll();
    }

    public function deleteLog(int $logId): void
    {
        $log = $this->logRepository->find($logId);
        if ($log) {
            $this->entityManager->remove($log);
            $this->entityManager->flush();
        }
    }

    public function getAllLogs(): array
    {
        return $this->logRepository->findAll();
    }

    public function updateLogType(int $logId, string $newType): bool
    {
        $log = $this->logRepository->find($logId);
        if (!$log) {
            return false;
        }

        $log->setType($newType);
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return true;
    }

    public function getFilteredLogs(?string $channel, ?string $type, ?string $startDate, ?string $endDate): array
    {
        return $this->logRepository->findFiltered($channel, $type, $startDate, $endDate);
    }

    public function getAvailableChannels(): array
    {
        return $this->logRepository->findDistinctChannels();
    }

    public function getAvailableTypes(): array
    {
        return $this->logRepository->findDistinctTypes();
    }

    public function handleLogUpload(array $file, int $userId, string $username): string
    {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE) {
            return "File too large: " . ini_get('upload_max_filesize');
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            return "Upload error: " . $file['error'];
        }

        $tempPath = $file['tmp_name'];
        $targetPath = 'dev.log';

        if (!move_uploaded_file($tempPath, $targetPath)) {
            return "Error saving file";
        }

        // Clear old logs - tasks will be cascade deleted by foreign key constraints
        $this->deleteAllLogs();

        // Add notification for the upload
        $this->notificationService->createNotification($userId, "Log file was uploaded by {$username}");

        // Reimport logs
        $this->importLogsFromFile($targetPath);
        return "Log file updated successfully";
    }

    public function getPendingStats(): array
    {
        // Get this from TaskRepository directly to avoid circular dependency
        return $this->entityManager->getRepository(\App\Entity\Task::class)->getPendingStats();
    }

    private function parseLogLine(string $line): ?array
    {
        $pattern = '/^\[(.*?)\] (\w+)\.(\w+): (.*)$/';
        if (preg_match($pattern, $line, $matches)) {
            $datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $matches[1]);
            return [
                'datetime' => $datetime ?: new \DateTime(),
                'channel' => $matches[2],
                'type' => $matches[3],
                'description' => $matches[4]
            ];
        }
        return null;
    }
}