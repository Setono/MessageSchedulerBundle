<?php

declare(strict_types=1);

namespace Setono\MessageSchedulerBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\Tools\SchemaValidator;
use Nyholm\BundleTest\AppKernel;
use Nyholm\BundleTest\BaseBundleTestCase;
use Nyholm\BundleTest\CompilerPass\PublicServicePass;
use Setono\MessageSchedulerBundle\Command\DispatchCommand;
use Setono\MessageSchedulerBundle\Dispatcher\Dispatcher;
use Setono\MessageSchedulerBundle\Factory\ScheduledMessageFactory;
use Setono\MessageSchedulerBundle\Message\Handler\DispatchMessageHandler;
use Setono\MessageSchedulerBundle\Repository\ScheduledMessageRepository;
use Setono\MessageSchedulerBundle\SetonoMessageSchedulerBundle;

final class SetonoMessageSchedulerBundleTest extends BaseBundleTestCase
{
    protected function getBundleClass(): string
    {
        return SetonoMessageSchedulerBundle::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->addCompilerPass(new PublicServicePass('|setono_message_scheduler.*|'));
    }

    protected function createKernel(): AppKernel
    {
        $kernel = parent::createKernel();

        $kernel->addConfigFile(__DIR__ . '/../src/Resources/config/app/config.yaml');
        $kernel->addConfigFile(__DIR__ . '/config/doctrine.yaml');
        $kernel->addBundle(DoctrineBundle::class);

        return $kernel;
    }

    /**
     * @test
     */
    public function it_inits(): void
    {
        $this->bootKernel();

        $this->assertServices([
            // commands
            'setono_message_scheduler.command.dispatch' => DispatchCommand::class,

            // dispatchers
            'setono_message_scheduler.dispatcher.default' => Dispatcher::class,

            // factories
            'setono_message_scheduler.factory.scheduled_message' => ScheduledMessageFactory::class,

            // message handlers
            'setono_message_scheduler.message_handler.dispatch_message' => DispatchMessageHandler::class,

            // repositories
            'setono_message_scheduler.repository.scheduled_message' => ScheduledMessageRepository::class,
        ]);
    }

    /**
     * @test
     */
    public function it_validates_doctrine_entity_configuration(): void
    {
        $this->bootKernel();

        $container = $this->getContainer();
        $manager = $container->get('doctrine')->getManager();

        $schemaValidator = new SchemaValidator($manager);
        $errors = $schemaValidator->validateMapping();

        self::assertCount(0, $errors);
    }

    private function assertServices(array $services): void
    {
        $container = $this->getContainer();

        foreach ($services as $id => $class) {
            self::assertTrue($container->has($id));
            $service = $container->get($id);
            self::assertInstanceOf($class, $service);
        }
    }
}
