<?php

declare(strict_types=1);

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\AfterTestMethodFinished;
use PHPUnit\Event\Test\AfterTestMethodFinishedSubscriber;
use PHPUnit\Event\Test\PreparationStarted as TestStartedEvent;
use PHPUnit\Event\Test\PreparationStartedSubscriber as TestStartedSubscriber;
use PHPUnit\Event\TestRunner\Finished as TestRunnerFinishedEvent;
use PHPUnit\Event\TestRunner\FinishedSubscriber as TestRunnerFinishedSubscriber;
use PHPUnit\Event\TestRunner\Started as TestRunnerStartedEvent;
use PHPUnit\Event\TestRunner\StartedSubscriber as TestRunnerStartedSubscriber;
use PHPUnit\Event\TestSuite\Started as TestSuiteStartedEvent;
use PHPUnit\Event\TestSuite\StartedSubscriber as TestSuiteStartedSubscriber;
use PHPUnit\Event\TestSuite\TestSuiteForTestClass;
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
     * Rolls back what the previous test left, then begins a new static transaction or disables static connections.
     * With $keepSavepointLevel, rolls back only to the innermost savepoint level when there is one.
     *
     * @internal
     */
    public static function restartTransaction(bool $skip, bool $keepSavepointLevel = false): void
    {
        if ($keepSavepointLevel && StaticDriver::getSavepointLevel() > 0) {
            StaticDriver::rollBackToSavepoint();
        } else {
            self::rollBack();
        }

        if ($skip) {
            self::skipTransaction();

            return;
        }

        self::unskipTransaction();
        self::beginTransaction();
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

        if ($reflectionClass->hasMethod($methodName = $test->methodName())
            && $reflectionClass->getMethod($methodName)->getAttributes(SkipDatabaseRollback::class)
        ) {
            return true;
        }

        return self::hasClassSkipAttribute($test->className());
    }

    /**
     * @internal
     *
     * @param class-string $className
     */
    public static function hasClassSkipAttribute(string $className): bool
    {
        $reflectionClass = new \ReflectionClass($className);

        do {
            if ($reflectionClass->getAttributes(SkipDatabaseRollback::class)) {
                return true;
            }
        } while (($reflectionClass = $reflectionClass->getParentClass())
            && $reflectionClass->name !== TestCase::class
        );

        return false;
    }

    /**
     * @internal
     */
    public static function warnAboutOpenTransactions(): void
    {
        if (!self::$transactionStarted) {
            return;
        }

        $keys = StaticDriver::getConnectionKeysWithOpenTransactions();

        if ($keys === []) {
            return;
        }

        trigger_error(sprintf(
            'A transaction was started but never committed or rolled back on connection(s) "%s". Outside of tests its changes would be lost.',
            implode('", "', $keys),
        ), E_USER_WARNING);
    }

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class implements TestRunnerStartedSubscriber {
            public function notify(TestRunnerStartedEvent $event): void
            {
                StaticDriver::setKeepStaticConnections(true);
            }
        });

        // emitted before setUpBeforeClass(), whose changes then follow the attribute of their own class
        // instead of depending on the previous test
        $facade->registerSubscriber(new class implements TestSuiteStartedSubscriber {
            public function notify(TestSuiteStartedEvent $event): void
            {
                $testSuite = $event->testSuite();

                if ($testSuite instanceof TestSuiteForTestClass) {
                    PHPUnitExtension::restartTransaction(PHPUnitExtension::hasClassSkipAttribute($testSuite->className()));
                }
            }
        });

        $facade->registerSubscriber(new class implements TestStartedSubscriber {
            public function notify(TestStartedEvent $event): void
            {
                PHPUnitExtension::restartTransaction(PHPUnitExtension::hasSkipAttribute($event->test()), keepSavepointLevel: true);
            }
        });

        if (!$parameters->has('warnAboutOpenTransactions') || $parameters->get('warnAboutOpenTransactions') !== 'false') {
            // PHPUnit only attributes a triggered warning to the test while its error handler is active, which is not the case anymore
            // once the test finished. This event is the last one within that window but is only emitted for tests with an after-hook
            // like tearDown(), which KernelTestCase and WebTestCase both have.
            $facade->registerSubscriber(new class implements AfterTestMethodFinishedSubscriber {
                public function notify(AfterTestMethodFinished $event): void
                {
                    PHPUnitExtension::warnAboutOpenTransactions();
                }
            });
        }

        $facade->registerSubscriber(new class implements TestRunnerFinishedSubscriber {
            public function notify(TestRunnerFinishedEvent $event): void
            {
                PHPUnitExtension::rollBack();
                StaticDriver::setKeepStaticConnections(false);
            }
        });
    }
}
