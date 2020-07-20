<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Workflow;

use Setono\MessageSchedulerBundle\Entity\ScheduledMessage;

final class ScheduledMessageWorkflow
{
    public const NAME = 'scheduled_message';

    public const TRANSITION_DISPATCH = 'dispatch';

    public const TRANSITION_PROCESS = 'process';

    public const TRANSITION_FAIL = 'fail';

    public const TRANSITION_SUCCEED = 'succeed';

    public static function getStates(): array
    {
        return [
            ScheduledMessage::STATE_PENDING,
            ScheduledMessage::STATE_DISPATCHED,
            ScheduledMessage::STATE_PROCESSING,
            ScheduledMessage::STATE_SUCCESSFUL,
            ScheduledMessage::STATE_FAILED,
        ];
    }
}
