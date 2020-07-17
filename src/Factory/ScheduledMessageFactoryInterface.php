<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Factory;

use DateTimeInterface;
use Setono\MessageSchedulerBundle\Entity\ScheduledMessage;

interface ScheduledMessageFactoryInterface
{
    /**
     * Creates a scheduled message entity based on an arbitrary serializable message.
     * If you want the message to be dispatched on a specific bus, supply that argument. Else it will be dispatched on
     * the default bus
     */
    public function create(object $message, DateTimeInterface $dispatchAt, string $bus = null): ScheduledMessage;
}
