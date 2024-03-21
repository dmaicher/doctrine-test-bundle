<?php

use Doctrine\Deprecations\Deprecation;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Tests\Functional\app\AppKernel;

require_once __DIR__.'/../vendor/autoload.php';

if (class_exists(Deprecation::class)) {
    Deprecation::enableWithTriggerError();
}

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

    $kernel->getContainer()->get('doctrine')->getConnection()->executeQuery('CREATE TABLE test (test VARCHAR(10))');
    $kernel->shutdown();
}

bootstrap();
