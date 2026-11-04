<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ContactMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContactMessage>
 */
class ContactMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactMessage::class);
    }

    public function countUnread(): int
    {
        return $this->count(['readAt' => null]);
    }

    /**
     * Purge RGPD : supprime les messages plus anciens que la date donnée.
     */
    public function deleteOlderThan(\DateTimeImmutable $limit): int
    {
        return (int) $this->createQueryBuilder('m')
            ->delete()
            ->where('m.createdAt < :limit')
            ->setParameter('limit', $limit)
            ->getQuery()
            ->execute();
    }
}
