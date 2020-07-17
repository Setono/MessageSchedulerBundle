<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Message\Command;

use Setono\MessageSchedulerBundle\Entity\ScheduledMessage;
use Webmozart\Assert\Assert;

final class DispatchMessage implements CommandInterface
{
    private string $scheduledMessageId;

    /**
     * @param ScheduledMessage|string|mixed $scheduledMessage
     */
    public function __construct($scheduledMessage)
    {
        if ($scheduledMessage instanceof ScheduledMessage) {
            $scheduledMessage = $scheduledMessage->getId();
        }

        Assert::string($scheduledMessage);

        $this->scheduledMessageId = $scheduledMessage;
    }

    public function getScheduledMessageId(): string
    {
        return $this->scheduledMessageId;
    }
}
