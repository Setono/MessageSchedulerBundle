<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Entity;

use DateTimeInterface;
use Ramsey\Uuid\Uuid;
use Webmozart\Assert\Assert;

class ScheduledMessage
{
    public const STATE_PENDING = 'pending';

    public const STATE_DISPATCHED = 'dispatched';

    public const STATE_PROCESSING = 'processing';

    public const STATE_FAILED = 'failed';

    public const STATE_SUCCESSFUL = 'successful';

    protected string $id;

    protected string $serializedMessage;

    protected DateTimeInterface $dispatchAt;

    protected ?string $bus;

    protected string $state = self::STATE_PENDING;

    protected array $errors = [];

    protected ?int $version = null;

    public function __construct(string $serializedMessage, DateTimeInterface $dispatchAt, string $bus = null)
    {
        $this->id = (string) Uuid::uuid4();
        $this->serializedMessage = $serializedMessage;
        $this->dispatchAt = $dispatchAt;
        $this->bus = $bus;
    }

    public static function getStates(): array
    {
        return [
            self::STATE_PENDING => self::STATE_PENDING,
            self::STATE_DISPATCHED => self::STATE_DISPATCHED,
            self::STATE_PROCESSING => self::STATE_PROCESSING,
            self::STATE_SUCCESSFUL => self::STATE_SUCCESSFUL,
            self::STATE_FAILED => self::STATE_FAILED,
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSerializedMessage(): string
    {
        return $this->serializedMessage;
    }

    public function getDispatchAt(): DateTimeInterface
    {
        return $this->dispatchAt;
    }

    public function getBus(): ?string
    {
        return $this->bus;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        Assert::oneOf($state, self::getStates());

        $this->state = $state;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function resetErrors(): void
    {
        $this->errors = [];
    }
}
