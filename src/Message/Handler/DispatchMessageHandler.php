<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Message\Handler;

use const DATE_ATOM;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use RuntimeException;
use Safe\DateTime;
use function Safe\ini_set;
use function Safe\sprintf;
use Setono\MessageSchedulerBundle\Entity\ScheduledMessage;
use Setono\MessageSchedulerBundle\Message\Command\DispatchScheduledMessage;
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

    public function __invoke(DispatchScheduledMessage $message): void
    {
        /** @var ScheduledMessage|null $scheduledMessage */
        $scheduledMessage = $this->scheduledMessageRepository->find($message->getScheduledMessageId());
        if (null === $scheduledMessage) {
            throw new UnrecoverableMessageHandlingException(sprintf(
                'The scheduled message with id, "%s" does not exist', $message->getScheduledMessageId()
            ));
        }

        $scheduledMessage->resetErrors();

        $now = new DateTime();
        if ($scheduledMessage->getDispatchAt() > $now) {
            throw new UnrecoverableMessageHandlingException(sprintf(
                'The scheduled message with id, "%s" is not eligible to be dispatched yet. The dispatch timestamp is %s, while the time now is %s',
                $message->getScheduledMessageId(), $scheduledMessage->getDispatchAt()->format(DATE_ATOM), $now->format(DATE_ATOM)
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

        $messageToBeDispatched = $this->unserialize($scheduledMessage->getSerializedMessage());

        $stamps = [];

        $bus = $scheduledMessage->getBus();
        if (null !== $bus) {
            $stamps[] = new BusNameStamp($bus);
        }

        try {
            // notice that this try-catch block won't handle (and therefore log) any errors happening,
            // if the $messageToBeDispatched is routed on an async transport
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

    /**
     * This code is taken from \Symfony\Component\Messenger\Transport\Serialization\PhpSerializer
     *
     * todo extract to a separate library
     */
    private function unserialize(string $str): object
    {
        $signalingException = new InvalidArgumentException(sprintf('Could not decode message using PHP serialization: %s.', $str));
        $prevUnserializeHandler = ini_set('unserialize_callback_func', self::class . '::handleUnserializeCallback');

        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MissingClosureReturnType
         */
        $prevErrorHandler = set_error_handler(function ($type, $msg, $file, $line, $context = []) use (&$prevErrorHandler, $signalingException) {
            if (__FILE__ === $file) {
                throw $signalingException;
            }

            return null !== $prevErrorHandler ? $prevErrorHandler($type, $msg, $file, $line, $context) : false;
        });

        try {
            /** @var object $messageToBeDispatched */
            $messageToBeDispatched = unserialize($str, [
                'allowed_classes' => true,
            ]);
        } finally {
            restore_error_handler();
            ini_set('unserialize_callback_func', $prevUnserializeHandler);
        }

        return $messageToBeDispatched;
    }

    /**
     * @internal
     */
    public static function handleUnserializeCallback(string $class): void
    {
        throw new InvalidArgumentException(sprintf('Message class "%s" not found during unserialization.', $class));
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
