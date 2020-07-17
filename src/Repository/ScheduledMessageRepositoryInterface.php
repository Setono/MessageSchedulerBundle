<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Repository;

use Doctrine\Persistence\ObjectRepository;
use Setono\MessageSchedulerBundle\Entity\ScheduledMessage;

/**
 * @extends \Doctrine\Persistence\ObjectRepository<ScheduledMessage>
 */
interface ScheduledMessageRepositoryInterface extends ObjectRepository
{
    /**
     * @return ScheduledMessage[]
     */
    public function findDispatchable(): array;
}
