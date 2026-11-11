<?php

declare(strict_types=1);

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestCollection;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\AfterTestMethodFinished;
use PHPUnit\Event\Test\AfterTestMethodFinishedSubscriber;
use PHPUnit\Event\Test\BeforeFirstTestMethodFinished;
use PHPUnit\Event\Test\BeforeFirstTestMethodFinishedSubscriber;
use PHPUnit\Event\Test\Passed as TestPassedEvent;
use PHPUnit\Event\Test\PassedSubscriber as TestPassedSubscriber;
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
     * Index of each test of the current class in the order they run, by test id.
     *
     * @var array<string, int>
     */
    private static array $testIndexes = [];

    /**
     * Index of the last test depending, directly or not, on each test method of the current class.
     *
     * @var array<string, int>
     */
    private static array $lastDependentIndexes = [];

    /**
     * Index of the last test needing each savepoint level created for #[KeepDatabaseChanges], innermost level last.
     *
     * @var list<int>
     */
    private static array $keptLevels = [];

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

        if (self::hasMethodAttribute($test, SkipDatabaseRollback::class)) {
            return true;
        }

        return self::hasClassAttribute($test->className(), SkipDatabaseRollback::class);
    }

    /**
     * @internal
     *
     * @param class-string $attributeName
     */
    public static function hasMethodAttribute(Test $test, string $attributeName): bool
    {
        if (!$test instanceof TestMethod) {
            return false;
        }

        $reflectionClass = new \ReflectionClass($test->className());

        return $reflectionClass->hasMethod($methodName = $test->methodName())
            && $reflectionClass->getMethod($methodName)->getAttributes($attributeName) !== [];
    }

    /**
     * @internal
     *
     * @param class-string $className
     * @param class-string $attributeName
     */
    public static function hasClassAttribute(string $className, string $attributeName): bool
    {
        $reflectionClass = new \ReflectionClass($className);

        do {
            if ($reflectionClass->getAttributes($attributeName)) {
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
    public static function collectDependentTests(TestCollection $tests): void
    {
        self::$testIndexes = self::$lastDependentIndexes = self::$keptLevels = [];
        $dependencies = [];

        foreach ($tests as $index => $test) {
            if (!$test instanceof TestMethod) {
                continue;
            }

            self::$testIndexes[$test->id()] = $index;
            $dependencies[$test->methodName()] ??= [];

            foreach ($test->metadata()->isDependsOnMethod() as $depends) {
                if ($depends->className() === $test->className()) {
                    // a dependency runs before the tests depending on it, so its own dependencies are known already
                    $dependencies[$test->methodName()] += [$depends->methodName() => true] + ($dependencies[$depends->methodName()] ?? []);
                }
            }

            foreach (array_keys($dependencies[$test->methodName()]) as $methodName) {
                self::$lastDependentIndexes[$methodName] = $index;
            }
        }
    }

    /**
     * @internal
     */
    public static function keepDatabaseChanges(Test $test): void
    {
        // without static connections, like in a test using #[SkipDatabaseRollback], the changes are committed
        if (!StaticDriver::isKeepStaticConnections() || !self::hasMethodAttribute($test, KeepDatabaseChanges::class)) {
            return;
        }

        // no test depends on this one
        if (!isset(self::$lastDependentIndexes[$test->methodName()])) {
            return;
        }

        StaticDriver::createSavepoint();
        self::$keptLevels[] = self::$lastDependentIndexes[$test->methodName()];
    }

    /**
     * Removes the savepoint levels created for #[KeepDatabaseChanges] that neither this test nor the following ones need.
     * A level is only removed once the ones created after it are, as they are nested.
     *
     * @internal
     */
    public static function rollBackKeptDatabaseChanges(Test $test): void
    {
        while (self::$keptLevels !== [] && end(self::$keptLevels) < self::$testIndexes[$test->id()]) {
            array_pop(self::$keptLevels);
            StaticDriver::rollBackAndReleaseSavepoint();
        }
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
                    PHPUnitExtension::restartTransaction(PHPUnitExtension::hasClassAttribute($testSuite->className(), SkipDatabaseRollback::class));
                    PHPUnitExtension::collectDependentTests($testSuite->tests());
                }
            }
        });

        // emitted once setUpBeforeClass() and the other before-class methods ran
        $facade->registerSubscriber(new class implements BeforeFirstTestMethodFinishedSubscriber {
            public function notify(BeforeFirstTestMethodFinished $event): void
            {
                // without static connections, like in a class using #[SkipDatabaseRollback], the changes are committed
                if (StaticDriver::isKeepStaticConnections()
                    && PHPUnitExtension::hasClassAttribute($event->testClassName(), ShareSetUpBeforeClassChanges::class)
                ) {
                    StaticDriver::createSavepoint();
                }
            }
        });

        $facade->registerSubscriber(new class implements TestStartedSubscriber {
            public function notify(TestStartedEvent $event): void
            {
                PHPUnitExtension::rollBackKeptDatabaseChanges($event->test());
                PHPUnitExtension::restartTransaction(PHPUnitExtension::hasSkipAttribute($event->test()), keepSavepointLevel: true);
            }
        });

        // emitted after tearDown(), and only for a test that passed: the changes of any other test are rolled back
        $facade->registerSubscriber(new class implements TestPassedSubscriber {
            public function notify(TestPassedEvent $event): void
            {
                PHPUnitExtension::keepDatabaseChanges($event->test());
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
