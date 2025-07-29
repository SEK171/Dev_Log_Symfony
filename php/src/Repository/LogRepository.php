<?php

namespace App\Repository;

use App\Entity\Log;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

// Same as a DAO
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['datetime' => 'DESC']);
    }

    public function deleteAll(): void
    {
        $this->createQueryBuilder('l')
            ->delete()
            ->getQuery()
            ->execute();
    }

    public function updateType(int $id, string $newType): bool
    {
        $result = $this->createQueryBuilder('l')
            ->update()
            ->set('l.type', ':type')
            ->where('l.id = :id')
            ->setParameter('type', $newType)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
        
        return $result > 0;
    }

    public function findFiltered(?string $channel, ?string $type, ?string $startDate, ?string $endDate): array
    {
        $qb = $this->createQueryBuilder('l');

        if (!empty($channel)) {
            $qb->andWhere('l.channel = :channel')
               ->setParameter('channel', $channel);
        }

        if (!empty($type)) {
            $qb->andWhere('l.type = :type')
               ->setParameter('type', $type);
        }

        if (!empty($startDate)) {
            $qb->andWhere('l.datetime >= :startDate')
               ->setParameter('startDate', new \DateTime($startDate));
        }

        if (!empty($endDate)) {
            $endDateTime = new \DateTime($endDate);
            $endDateTime->add(new \DateInterval('P1D'));
            $qb->andWhere('l.datetime < :endDate')
               ->setParameter('endDate', $endDateTime);
        }

        return $qb->orderBy('l.datetime', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function findDistinctChannels(): array
    {
        $result = $this->createQueryBuilder('l')
            ->select('DISTINCT l.channel')
            ->where('l.channel IS NOT NULL')
            ->orderBy('l.channel', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'channel');
    }

    public function findDistinctTypes(): array
    {
        $result = $this->createQueryBuilder('l')
            ->select('DISTINCT l.type')
            ->where('l.type IS NOT NULL')
            ->orderBy('l.type', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'type');
    }
}