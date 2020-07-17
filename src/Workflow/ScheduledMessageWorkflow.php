<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Workflow;

final class ScheduledMessageWorkflow
{
    public const NAME = 'scheduled_message';

    public const TRANSITION_DISPATCH = 'dispatch';

    public const TRANSITION_PROCESS = 'process';

    public const TRANSITION_FAIL = 'fail';

    public const TRANSITION_SUCCEED = 'succeed';
}
