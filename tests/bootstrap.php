<?php

require_once __DIR__.'/../vendor/autoload.php';

function bootstrap(): void
{
    $kernel = new \Tests\Functional\app\AppKernel('test', true);
    $kernel->boot();

    $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
    $application->setAutoExit(false);

    $application->run(new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'doctrine:database:drop',
        '--if-exists' => '1',
        '--force' => '1',
    ]));

    $application->run(new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'doctrine:database:create',
    ]));

    $application->run(new \Symfony\Component\Console\Input\ArrayInput([
        'command' => $application->has('dbal:run-sql') ? 'dbal:run-sql' : 'doctrine:query:sql',
        'sql' => 'CREATE TABLE test (test VARCHAR(10))',
    ]));

    $kernel->shutdown();
}

bootstrap();
