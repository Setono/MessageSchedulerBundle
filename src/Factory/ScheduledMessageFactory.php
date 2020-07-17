<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Factory;

use DateTimeInterface;
use Setono\MessageSchedulerBundle\Entity\ScheduledMessage;

final class ScheduledMessageFactory implements ScheduledMessageFactoryInterface
{
    public function create(object $message, DateTimeInterface $dispatchAt, string $bus = null): ScheduledMessage
    {
        return new ScheduledMessage(serialize($message), $dispatchAt, $bus);
    }
}
