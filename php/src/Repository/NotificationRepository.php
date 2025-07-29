<?php
namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteAll(): void
    {
        $this->createQueryBuilder('n')
            ->delete()
            ->getQuery()
            ->execute();
    }

    public function deleteByUser(int $userId): void
    {
        $this->createQueryBuilder('n')
            ->delete()
            ->where('n.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function markAsRead(int $id): bool
    {
        $result = $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':isRead')
            ->where('n.id = :id')
            ->setParameter('isRead', true)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
        
        return $result > 0;
    }
}