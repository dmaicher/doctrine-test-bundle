<?php

declare(strict_types=1);

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted as TestStartedEvent;
use PHPUnit\Event\Test\PreparationStartedSubscriber as TestStartedSubscriber;
use PHPUnit\Event\TestRunner\Finished as TestRunnerFinishedEvent;
use PHPUnit\Event\TestRunner\FinishedSubscriber as TestRunnerFinishedSubscriber;
use PHPUnit\Event\TestRunner\Started as TestRunnerStartedEvent;
use PHPUnit\Event\TestRunner\StartedSubscriber as TestRunnerStartedSubscriber;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * @final
 */
class PHPUnitExtension implements Extension
{
    /**
     * @internal
     */
    public static bool $transactionStarted = false;
    private static bool $transactionSkipped = false;

    /**
     * @internal
     */
    public static function beginTransaction(): void
    {
        if (self::$transactionStarted) {
            return;
        }

        StaticDriver::beginTransaction();
        self::$transactionStarted = true;
    }

    /**
     * @internal
     */
    public static function rollBack(): void
    {
        if (!self::$transactionStarted) {
            return;
        }

        StaticDriver::rollBack();
        self::$transactionStarted = false;
    }

    /**
     * @internal
     */
    public static function skipTransaction(): void
    {
        self::$transactionSkipped = true;
        StaticDriver::setKeepStaticConnections(false);
    }

    /**
     * @internal
     */
    public static function unskipTransaction(): void
    {
        if (!self::$transactionSkipped) {
            // we only do it if it was skipped due to an attribute on the test
            // otherwise we keep skipping transactions as it was manually done during a test (like in setUpBeforeClass)
            return;
        }

        self::$transactionSkipped = false;
        StaticDriver::setKeepStaticConnections(true);
    }

    /**
     * @internal
     */
    public static function hasSkipAttribute(Test $test): bool
    {
        if (!$test instanceof TestMethod) {
            return false;
        }

        $reflectionClass = new \ReflectionClass($test->className());
        if ($reflectionClass->getAttributes(SkipDatabaseRollback::class)) {
            return true;
        }

        if ($reflectionClass->hasMethod($methodName = $test->methodName())
            && $reflectionClass->getMethod($methodName)->getAttributes(SkipDatabaseRollback::class)
        ) {
            return true;
        }

        while (($reflectionClass = $reflectionClass->getParentClass())
            && $reflectionClass->name !== TestCase::class
        ) {
            if ($reflectionClass->getAttributes(SkipDatabaseRollback::class)) {
                return true;
            }
        }

        return false;
    }

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class implements TestRunnerStartedSubscriber {
            public function notify(TestRunnerStartedEvent $event): void
            {
                StaticDriver::setKeepStaticConnections(true);
            }
        });

        $facade->registerSubscriber(new class implements TestStartedSubscriber {
            public function notify(TestStartedEvent $event): void
            {
                PHPUnitExtension::rollBack();

                if (PHPUnitExtension::hasSkipAttribute($event->test())) {
                    PHPUnitExtension::skipTransaction();

                    return;
                }

                PHPUnitExtension::unskipTransaction();
                PHPUnitExtension::beginTransaction();
            }
        });

        $facade->registerSubscriber(new class implements TestRunnerFinishedSubscriber {
            public function notify(TestRunnerFinishedEvent $event): void
            {
                PHPUnitExtension::rollBack();
                StaticDriver::setKeepStaticConnections(false);
            }
        });
    }
}
