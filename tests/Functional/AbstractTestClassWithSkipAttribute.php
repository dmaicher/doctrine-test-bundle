<?php

declare(strict_types=1);

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use PHPUnit\Framework\TestCase;

#[SkipDatabaseRollback]
abstract class AbstractTestClassWithSkipAttribute extends TestCase
{
}
