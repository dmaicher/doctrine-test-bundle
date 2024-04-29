<?php

use App\Kernel;
use Doctrine\Deprecations\Deprecation;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

if (class_exists(Deprecation::class)) {
    Deprecation::enableWithTriggerError();
}

bootstrap();
function bootstrap(): void
{
    $kernel = new Kernel('test', true);
    $kernel->boot();

    $application = new Application($kernel);
    $application->setCatchExceptions(false);
    $application->setAutoExit(false);

        // check if existing DB is up-to-date
        // if not drop it, recreate and migrate
        $output = $application->run(new ArrayInput(['command' => 'doctrine:migrations:up-to-date', '--no-interaction' => true]));

        // some error
        if ($output == 1) loadDatabaseAndMigrate($application);


    $kernel->shutdown();
}

function loadDatabaseAndMigrate($application): void
{
    $application->run(new ArrayInput(['command' => 'doctrine:database:drop', '--if-exists' => '1', '--force' => '1',]));

    $application->run(new ArrayInput(['command' => 'doctrine:database:create', '--no-interaction' => true]));

    $application->run(new ArrayInput(['command' => 'doctrine:migrations:migrate', '--no-interaction' => true]));

}
