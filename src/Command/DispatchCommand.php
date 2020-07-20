<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Command;

use Setono\MessageSchedulerBundle\Dispatcher\DispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DispatchCommand extends Command
{
    protected static $defaultName = 'setono:message-scheduler:dispatch';

    private DispatcherInterface $dispatcher;

    public function __construct(DispatcherInterface $dispatcher)
    {
        parent::__construct();

        $this->dispatcher = $dispatcher;
    }

    protected function configure(): void
    {
        $this->setDescription('Will dispatch messages, that are eligible for dispatching');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dispatcher->dispatch();

        return 0;
    }
}
