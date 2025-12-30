<?php

declare(strict_types=1);

namespace DAMA\DoctrineTestBundle\Behat;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @final
 */
class BehatListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ExerciseCompleted::BEFORE => 'enableStaticConnection',
            ExerciseCompleted::AFTER => 'disableStaticConnection',
            ScenarioTested::BEFORE => ['beginTransaction', 255],
            ExampleTested::BEFORE => ['beginTransaction', 255],
            ScenarioTested::AFTER => ['rollBack', -255],
            ExampleTested::AFTER => ['rollBack', -255],
        ];
    }

    public function enableStaticConnection(): void
    {
        StaticDriver::setKeepStaticConnections(true);
    }

    public function disableStaticConnection(): void
    {
        StaticDriver::setKeepStaticConnections(false);
    }

    public function beginTransaction(): void
    {
        StaticDriver::beginTransaction();
    }

    public function rollBack(): void
    {
        StaticDriver::rollBack();
    }
}
