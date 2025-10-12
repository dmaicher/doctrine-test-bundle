<?php

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Dotenv\Dotenv;
use Tests\Functional\app\AppKernel;

require_once __DIR__.'/../vendor/autoload.php';

(new Dotenv())->loadEnv(path: __DIR__.'/../.env');

function bootstrap(): void
{
    $kernel = new AppKernel('test', true);
    $kernel->boot();

    $application = new Application($kernel);
    $application->setCatchExceptions(false);
    $application->setAutoExit(false);

    $application->run(new ArrayInput([
        'command' => 'doctrine:database:drop',
        '--if-exists' => '1',
        '--force' => '1',
    ]));

    $application->run(new ArrayInput([
        'command' => 'doctrine:database:create',
    ]));

    /** @var ManagerRegistry $registry */
    $registry = $kernel->getContainer()->get('doctrine');

    /** @var Connection $connection */
    $connection = $registry->getConnection();
    $connection->executeQuery('CREATE TABLE test (test VARCHAR(10))');

    $kernel->shutdown();
    restore_error_handler();
}

bootstrap();
