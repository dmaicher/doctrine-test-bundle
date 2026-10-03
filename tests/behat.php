<?php

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use DAMA\DoctrineTestBundle\Behat\ServiceContainer\DoctrineExtension;

return (new Config())
    ->withProfile(
        (new Profile('default', [
            'autoload' => ['' => '%paths.base%/Functional/features/bootstrap'],
        ]))
            ->withSuite(
                (new Suite('functional'))
                    ->withPaths('%paths.base%/Functional/features')
            )
            ->withExtension(new Extension(DoctrineExtension::class))
    )
;
