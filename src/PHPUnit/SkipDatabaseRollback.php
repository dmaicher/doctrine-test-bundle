<?php

declare(strict_types=1);

namespace DAMA\DoctrineTestBundle\PHPUnit;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
final class SkipDatabaseRollback
{
}
