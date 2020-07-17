<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Dispatcher;

interface DispatcherInterface
{
    /**
     * Will dispatch all 'dispatchable' messages
     */
    public function dispatch(): void;
}
