<?php
namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }

    public function deleteAll(): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->getQuery()
            ->execute();
    }

    public function findByAssignedTo(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.assignedTo = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteByAssignedTo(int $userId): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.assignedTo = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function updateStatus(int $id, string $newStatus): bool
    {
        $result = $this->createQueryBuilder('t')
            ->update()
            ->set('t.status', ':status')
            ->set('t.updatedAt', ':updatedAt')
            ->where('t.id = :id')
            ->setParameter('status', $newStatus)
            ->setParameter('updatedAt', new \DateTime())
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
        
        return $result > 0;
    }

    public function findAllWithDetails(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t', 'l', 'assignedBy', 'assignedTo')
            ->join('t.log', 'l')
            ->join('t.assignedBy', 'assignedBy')
            ->join('t.assignedTo', 'assignedTo')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAssignedToWithDetails(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->select('t', 'l', 'assignedBy')
            ->join('t.log', 'l')
            ->join('t.assignedBy', 'assignedBy')
            ->where('t.assignedTo = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getPendingStats(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('COUNT(t.id) as pending')
            ->addSelect('SUM(CASE WHEN l.type IN (:criticalTypes) THEN 1 ELSE 0 END) as critical')
            ->join('t.log', 'l')
            ->where('t.status = :status')
            ->setParameter('status', 'pending')
            ->setParameter('criticalTypes', ['error', 'critical'])
            ->getQuery()
            ->getSingleResult();

        return [
            'pending' => (int) ($result['pending'] ?? 0),
            'critical' => (int) ($result['critical'] ?? 0),
        ];
    }
}