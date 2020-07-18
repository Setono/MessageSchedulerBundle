<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Message\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;
use function Safe\sprintf;
use Setono\MessageSchedulerBundle\Entity\ScheduledMessage;
use Setono\MessageSchedulerBundle\Message\Command\DispatchMessage;
use Setono\MessageSchedulerBundle\Repository\ScheduledMessageRepositoryInterface;
use Setono\MessageSchedulerBundle\Workflow\ScheduledMessageWorkflow;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\RoutableMessageBus;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Workflow\Registry;
use Throwable;

final class DispatchMessageHandler implements MessageHandlerInterface
{
    private ?ObjectManager $objectManager = null;

    private RoutableMessageBus $routableMessageBus;

    private ScheduledMessageRepositoryInterface $scheduledMessageRepository;

    private Registry $workflowRegistry;

    private ManagerRegistry $managerRegistry;

    public function __construct(
        RoutableMessageBus $routableMessageBus,
        ScheduledMessageRepositoryInterface $scheduledMessageRepository,
        Registry $workflowRegistry,
        ManagerRegistry $managerRegistry
    ) {
        $this->routableMessageBus = $routableMessageBus;
        $this->scheduledMessageRepository = $scheduledMessageRepository;
        $this->workflowRegistry = $workflowRegistry;
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(DispatchMessage $message): void
    {
        /** @var ScheduledMessage|null $scheduledMessage */
        $scheduledMessage = $this->scheduledMessageRepository->find($message->getScheduledMessageId());
        if (null === $scheduledMessage) {
            throw new UnrecoverableMessageHandlingException(sprintf(
                'The scheduled message with id, "%s" does not exist', $message->getScheduledMessageId()
            ));
        }

        $workflow = $this->workflowRegistry->get($scheduledMessage, ScheduledMessageWorkflow::NAME);
        if (!$workflow->can($scheduledMessage, ScheduledMessageWorkflow::TRANSITION_PROCESS)) {
            throw new UnrecoverableMessageHandlingException(sprintf(
                'The scheduled message with id, "%s" could not enter the transition "%s". The state was: "%s"',
                $message->getScheduledMessageId(), ScheduledMessageWorkflow::TRANSITION_PROCESS, $scheduledMessage->getState()
            ));
        }
        $workflow->apply($scheduledMessage, ScheduledMessageWorkflow::TRANSITION_PROCESS);

        $objectManager = $this->getObjectManager($scheduledMessage);
        $objectManager->flush();

        /** @var object $messageToBeDispatched */
        $messageToBeDispatched = unserialize($scheduledMessage->getSerializedMessage(), [
            'allowed_classes' => [ScheduledMessage::class],
        ]);

        $stamps = [];

        $bus = $scheduledMessage->getBus();
        if (null !== $bus) {
            $stamps[] = new BusNameStamp($bus);
        }

        try {
            $this->routableMessageBus->dispatch(new Envelope($messageToBeDispatched, $stamps));

            $workflow->apply($scheduledMessage, ScheduledMessageWorkflow::TRANSITION_SUCCEED);
            $objectManager->flush();
        } catch (Throwable $e) {
            $originalException = $e;

            $workflow->apply($scheduledMessage, ScheduledMessageWorkflow::TRANSITION_FAIL);

            do {
                $scheduledMessage->addError($e->getMessage());
                $e = $e->getPrevious();
            } while (null !== $e);

            $objectManager->flush();

            throw $originalException;
        }
    }

    private function getObjectManager(object $object): ObjectManager
    {
        if (null === $this->objectManager) {
            $class = get_class($object);
            $manager = $this->managerRegistry->getManagerForClass($class);

            if (null === $manager) {
                throw new RuntimeException(sprintf('No object manager associated with the class, %s', $class));
            }

            $this->objectManager = $manager;
        }

        return $this->objectManager;
    }
}
