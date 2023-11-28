<?php

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Event\Test\Finished as TestFinishedEvent;
use PHPUnit\Event\Test\FinishedSubscriber as TestFinishedSubscriber;
use PHPUnit\Event\Test\PreparationStarted as TestStartedEvent;
use PHPUnit\Event\Test\PreparationStartedSubscriber as TestStartedSubscriber;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;
use PHPUnit\Event\TestRunner\Finished as TestRunnerFinishedEvent;
use PHPUnit\Event\TestRunner\FinishedSubscriber as TestRunnerFinishedSubscriber;
use PHPUnit\Event\TestRunner\Started as TestRunnerStartedEvent;
use PHPUnit\Event\TestRunner\StartedSubscriber as TestRunnerStartedSubscriber;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Runner\BeforeTestHook;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

if (class_exists(TestRunnerStartedEvent::class)) {
    /**
     * PHPUnit >= 10.
     */
    class PHPUnitExtension implements Extension
    {
        public static $rolledBack = false;

        public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
        {
            $facade->registerSubscriber(new class() implements TestRunnerStartedSubscriber {
                public function notify(TestRunnerStartedEvent $event): void
                {
                    StaticDriver::setKeepStaticConnections(true);
                }
            });

            $facade->registerSubscriber(new class() implements TestStartedSubscriber {
                public function notify(TestStartedEvent $event): void
                {
                    PHPUnitExtension::$rolledBack = false;
                    StaticDriver::beginTransaction();
                }
            });

            $facade->registerSubscriber(new class() implements SkippedSubscriber {
                public function notify(Skipped $event): void
                {
                    // this is a workaround to allow skipping tests within the setUp() method
                    // as for those cases there is no Finished event
                    PHPUnitExtension::$rolledBack = true;
                    StaticDriver::rollBack();
                }
            });

            $facade->registerSubscriber(new class() implements TestFinishedSubscriber {
                public function notify(TestFinishedEvent $event): void
                {
                    // we only roll back if we did not already do it in the SkippedSubscriber
                    if (!PHPUnitExtension::$rolledBack) {
                        StaticDriver::rollBack();
                    }
                }
            });

            $facade->registerSubscriber(new class() implements TestRunnerFinishedSubscriber {
                public function notify(TestRunnerFinishedEvent $event): void
                {
                    StaticDriver::setKeepStaticConnections(false);
                }
            });
        }
    }
} else {
    /**
     * PHPUnit < 10.
     */
    class PHPUnitExtension implements BeforeFirstTestHook, AfterLastTestHook, BeforeTestHook, AfterTestHook
    {
        public function executeBeforeFirstTest(): void
        {
            StaticDriver::setKeepStaticConnections(true);
        }

        public function executeBeforeTest(string $test): void
        {
            StaticDriver::beginTransaction();
        }

        public function executeAfterTest(string $test, float $time): void
        {
            StaticDriver::rollBack();
        }

        public function executeAfterLastTest(): void
        {
            StaticDriver::setKeepStaticConnections(false);
        }
    }
}
