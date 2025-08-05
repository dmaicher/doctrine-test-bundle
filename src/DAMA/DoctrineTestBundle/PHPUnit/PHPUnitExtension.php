<?php

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Event\Test\BeforeTestMethodErrored;
use PHPUnit\Event\Test\BeforeTestMethodErroredSubscriber;
use PHPUnit\Event\Test\BeforeTestMethodFailed;
use PHPUnit\Event\Test\BeforeTestMethodFailedSubscriber;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
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
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class PHPUnitExtension implements Extension
{
    public static bool $transactionStarted = false;

    public static function rollBack(): void
    {
        if (!self::$transactionStarted) {
            return;
        }

        StaticDriver::rollBack();
        self::$transactionStarted = false;
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
                StaticDriver::beginTransaction();
                PHPUnitExtension::$transactionStarted = true;
            }
        });

        $facade->registerSubscriber(new class implements SkippedSubscriber {
            public function notify(Skipped $event): void
            {
                // this is a workaround to allow skipping tests within the setUp() method
                // as for those cases there is no Finished event
                PHPUnitExtension::rollBack();
            }
        });

        $facade->registerSubscriber(new class implements TestFinishedSubscriber {
            public function notify(TestFinishedEvent $event): void
            {
                PHPUnitExtension::rollBack();
            }
        });

        if (interface_exists(BeforeTestMethodErroredSubscriber::class)) {
            $facade->registerSubscriber(new class implements BeforeTestMethodErroredSubscriber {
                public function notify(BeforeTestMethodErrored $event): void
                {
                    // needed for tests that error (or marked incomplete for PHPUnit < 12.2.0) during setUp()
                    PHPUnitExtension::rollBack();
                }
            });
        }

        if (interface_exists(BeforeTestMethodFailedSubscriber::class)) {
            $facade->registerSubscriber(new class implements BeforeTestMethodFailedSubscriber {
                public function notify(BeforeTestMethodFailed $event): void
                {
                    // needed for tests that fail (or marked incomplete for PHPUnit >= 12.2.0) during setUp()
                    PHPUnitExtension::rollBack();
                }
            });
        }

        $facade->registerSubscriber(new class implements ErroredSubscriber {
            public function notify(Errored $event): void
            {
                // needed as for errored tests the "Finished" event is not triggered
                PHPUnitExtension::rollBack();
            }
        });

        $facade->registerSubscriber(new class implements TestRunnerFinishedSubscriber {
            public function notify(TestRunnerFinishedEvent $event): void
            {
                StaticDriver::setKeepStaticConnections(false);
            }
        });
    }
}
