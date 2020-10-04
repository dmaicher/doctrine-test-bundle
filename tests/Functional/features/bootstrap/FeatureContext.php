<?php

use Behat\Behat\Context\Context;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\Functional\FunctionalTestTrait;

class FeatureContext implements Context
{
    use FunctionalTestTrait;

    /**
     * @BeforeSuite
     */
    public static function bootstrap(): void
    {
        $executableFinder = new PhpExecutableFinder();
        $php = $executableFinder->find(false);

        (new Process([$php, __DIR__.'/../../../bootstrap.php']))->mustRun();
    }
}
