<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

if (interface_exists(\Doctrine\DBAL\Driver\ExceptionConverterDriver::class)) {
    // dbal v2
    /**
     * Wraps a real connection and just skips the first call to beginTransaction as a transaction is already started on the underlying connection.
     */
    class StaticConnection extends AbstractStaticConnectionV2
    {
    }
} else {
    // dbal v3
    /**
     * Wraps a real connection and just skips the first call to beginTransaction as a transaction is already started on the underlying connection.
     */
    class StaticConnection extends AbstractStaticConnectionV3
    {
    }
}
