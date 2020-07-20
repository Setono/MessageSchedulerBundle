<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Tests\Dispatcher;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Setono\MessageSchedulerBundle\Dispatcher\Dispatcher;
use Setono\MessageSchedulerBundle\Entity\ScheduledMessage;
use Setono\MessageSchedulerBundle\Repository\ScheduledMessageRepositoryInterface;
use Setono\MessageSchedulerBundle\Workflow\ScheduledMessageWorkflow;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;

final class DispatcherTest extends TestCase
{
    /**
     * @test
     */
    public function it_dispatches(): void
    {
        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::any())->shouldBeCalled()->willReturn(new Envelope(new stdClass()));

        $scheduledMessage = new ScheduledMessage('test', new DateTime());

        $scheduledMessageRepository = $this->prophesize(ScheduledMessageRepositoryInterface::class);
        $scheduledMessageRepository
            ->findDispatchable()
            ->willReturn([$scheduledMessage])
        ;

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can($scheduledMessage, ScheduledMessageWorkflow::TRANSITION_DISPATCH)->willReturn(true);
        $workflow->apply($scheduledMessage, ScheduledMessageWorkflow::TRANSITION_DISPATCH)->shouldBeCalled();

        $workflowRegistry = $this->prophesize(Registry::class);
        $workflowRegistry
            ->get($scheduledMessage, ScheduledMessageWorkflow::NAME)
            ->willReturn($workflow)
        ;

        $manager = $this->prophesize(EntityManagerInterface::class);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(ScheduledMessage::class)->willReturn($manager);

        $dispatcher = new Dispatcher(
            $commandBus->reveal(), $scheduledMessageRepository->reveal(), $workflowRegistry->reveal(), $managerRegistry->reveal()
        );
        $dispatcher->dispatch();
    }
}
