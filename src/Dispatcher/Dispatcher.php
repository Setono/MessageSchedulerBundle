<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Dispatcher;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use RuntimeException;
use function Safe\sprintf;
use Setono\MessageSchedulerBundle\Message\Command\DispatchScheduledMessage;
use Setono\MessageSchedulerBundle\Repository\ScheduledMessageRepositoryInterface;
use Setono\MessageSchedulerBundle\Workflow\ScheduledMessageWorkflow;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;

final class Dispatcher implements DispatcherInterface
{
    private ?ObjectManager $objectManager = null;

    private MessageBusInterface $commandBus;

    private ScheduledMessageRepositoryInterface $scheduledMessageRepository;

    private Registry $workflowRegistry;

    private ManagerRegistry $managerRegistry;

    public function __construct(
        MessageBusInterface $commandBus,
        ScheduledMessageRepositoryInterface $scheduledMessageRepository,
        Registry $workflowRegistry,
        ManagerRegistry $managerRegistry
    ) {
        $this->commandBus = $commandBus;
        $this->scheduledMessageRepository = $scheduledMessageRepository;
        $this->workflowRegistry = $workflowRegistry;
        $this->managerRegistry = $managerRegistry;
    }

    public function dispatch(): void
    {
        $messages = $this->scheduledMessageRepository->findDispatchable();

        if (count($messages) === 0) {
            return;
        }

        $workflow = $this->getWorkflow($messages);
        $objectManager = $this->getObjectManager($messages);

        foreach ($messages as $message) {
            if (!$workflow->can($message, ScheduledMessageWorkflow::TRANSITION_DISPATCH)) {
                continue;
            }

            $workflow->apply($message, ScheduledMessageWorkflow::TRANSITION_DISPATCH);
            $objectManager->flush();

            $this->commandBus->dispatch(new DispatchScheduledMessage($message));
        }
    }

    /**
     * This method presumes that all the objects are of the same class
     * therefore it returns the workflow for the first object
     *
     * @param object[] $objects
     */
    private function getWorkflow(array $objects): Workflow
    {
        $obj = current($objects);
        if (false === $obj) {
            throw new LogicException('An empty array of objects were passed to this method');
        }

        return $this->workflowRegistry->get($obj, ScheduledMessageWorkflow::NAME);
    }

    /**
     * This method presumes that all the objects are of the same class
     * therefore it returns the object manager for the first object's class
     *
     * @param object[] $objects
     */
    private function getObjectManager(array $objects): ObjectManager
    {
        if (null === $this->objectManager) {
            $obj = current($objects);
            if (false === $obj) {
                throw new LogicException('An empty array of objects were passed to this method');
            }

            $class = get_class($obj);
            $manager = $this->managerRegistry->getManagerForClass($class);

            if (null === $manager) {
                throw new RuntimeException(sprintf('No object manager associated with the class, %s', $class));
            }

            $this->objectManager = $manager;
        }

        return $this->objectManager;
    }
}
