<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Safe\DateTime;
use Setono\MessageSchedulerBundle\Entity\ScheduledMessage;

/**
 * @extends \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository<ScheduledMessage>
 */
class ScheduledMessageRepository extends ServiceEntityRepository implements ScheduledMessageRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledMessage::class);
    }

    public function findDispatchable(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.dispatchAt <= :now')
            ->andWhere('o.state = :state')
            ->setParameter('now', new DateTime())
            ->setParameter('state', ScheduledMessage::STATE_PENDING)
            ->setMaxResults(100) // this is just a very basic 'hack' to prevent memory problems
            ->getQuery()
            ->getResult()
        ;
    }
}
