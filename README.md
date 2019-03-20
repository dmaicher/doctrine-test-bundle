[![PHP Version](https://img.shields.io/badge/php-%5E7.1-blue.svg)](https://img.shields.io/badge/php-%5E7.1-blue.svg)
[![Stable release][Last stable image]][Packagist link]
[![Unstable release][Last unstable image]][Packagist link]

[![Build status][Master build image]][Master build link]
[![Coverage Status][Master coverage image]][Master scrutinizer link]
[![Scrutinizer][Master scrutinizer image]][Master scrutinizer link]

### What does it do? :) 

This bundle provides features that help you run your Symfony-framework-based App's testsuite more efficiently with isolated tests.

It provides a `StaticDriver` that will wrap your originally configured `Driver` class (like `DBAL\Driver\PDOMysql\Driver`) and keeps a database connection statically in the current php process.

With the help of a PHPUnit listener class it will begin a transaction before every testcase and roll it back again after the test finished for all configured DBAL connections. This results in a performance boost as there is no need to rebuild the schema, import a backup SQL dump or re-insert fixtures before every testcase. As long as you avoid issuing DDL queries that might result in implicit transaction commits (Like `ALTER TABLE`, `DROP TABLE` etc; see https://wiki.postgresql.org/wiki/Transactional_DDL_in_PostgreSQL:_A_Competitive_Analysis) your tests will be isolated and all see the same database state.

It also includes a `StaticArrayCache` that will be automatically configured as meta data & query cache for all EntityManagers. This improved the speed and memory usage for my testsuites dramatically! This is especially beneficial if you have a lot of tests that boot kernels (like Controller tests or ContainerAware tests) and use Doctrine entities.

### How to install and use this Bundle?

1. install via composer

    ```sh
    composer require --dev dama/doctrine-test-bundle
    ```

2. Enable the bundle for your test environment in your `AppKernel.php`

    ```php
    if (in_array($env, ['dev', 'test'])) {
        ...
        if ($env === 'test') {
            $bundles[] = new DAMA\DoctrineTestBundle\DAMADoctrineTestBundle();
        }
    }
    ```
    
    Note: if you are using symfony flex and you are allowing contrib recipes (`extra.symfony.allow-contrib=true`) then the bundle will be automatically enabled for the `'test'` environment. See https://github.com/symfony/recipes-contrib/tree/master/dama/doctrine-test-bundle
    
3. Starting from PHPUnit version 8, add the extension in your xml config (e.g. `app/phpunit.xml`)

    ```xml
    <phpunit>
        ...
        <extensions>
            <extension class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension" />
        </extensions>
    </phpunit>
    ```

    For lower PHPUnit versions add the listener in your xml config (e.g. `app/phpunit.xml`) 

    ```xml
    <phpunit>
        ...
        <listeners>
            <listener class="\DAMA\DoctrineTestBundle\PHPUnit\PHPUnitListener" />
        </listeners>
    </phpunit>
    ```
    
4. Make sure you also have `phpunit/phpunit` available as a `dev` dependency (**versions 6, 7 and 8 are supported with the built in listener**) to run your tests. 
   Alternatively this bundle is also compatible with `symfony/phpunit-bridge` and its `simple-phpunit` script. 
   (Note: you may need to make sure the phpunit-bridge requires the correct PHPUnit 6+ Version using the environment variable `SYMFONY_PHPUNIT_VERSION`). 

5. That's it! From now on whatever changes you do to the database within each single testcase (be it a `WebTestCase` or a `KernelTestCase` or any custom test) are automatically rolled back for you :blush:
    
### Configuration

The bundle exposes a configuration that looks like this by default:
    
```yaml
dama_doctrine_test:
  enable_static_connection: true
  enable_static_meta_data_cache: true
  enable_static_query_cache: true
```

### Example

An example usage can be seen within the functional tests included in this bundle: https://github.com/dmaicher/doctrine-test-bundle/tree/master/tests

- initial dabatase bootstrap is done using phpunit bootstrap file: https://github.com/dmaicher/doctrine-test-bundle/blob/master/tests/phpunit.bootstrap.php
- several tests that make sure any changes from previous tests are rolled back: https://github.com/dmaicher/doctrine-test-bundle/blob/master/tests/Functional/FunctionalTest.php

This bundle is also used on the official Symfony Demo testsuite: https://github.com/symfony/demo

### Debugging 

Sometimes it can be useful to be able to debug the database contents when a test failed. As normally all changes are rolled back automatically you can do this manually:

```php
public function testMyTestCaseThatINeedToDebug()
{
    // ... something thats changes the DB state
    \DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver::commit();
    die;
    // now the DB changes are actually persisted and you can debug them
}
```

### Troubleshooting

In case you are running (maybe without knowing it) queries during your tests that are implicitly committing any open transaction 
(see https://dev.mysql.com/doc/refman/8.0/en/implicit-commit.html for example) you might see an error like this:

```
Doctrine\DBAL\Driver\PDOException: SQLSTATE[42000]: Syntax error or access violation: 1305 SAVEPOINT DOCTRINE2_SAVEPOINT_2 does not exist
```

Currently there is no way for this bundle to work with those queries as they simply cannot be rolled back after the test case finished.

See also https://github.com/dmaicher/doctrine-test-bundle/issues/58
    
[Last stable image]: https://poser.pugx.org/dama/doctrine-test-bundle/version.svg
[Last unstable image]: https://poser.pugx.org/dama/doctrine-test-bundle/v/unstable.svg
[Master build image]: https://travis-ci.org/dmaicher/doctrine-test-bundle.svg?branch=master
[Master scrutinizer image]: https://scrutinizer-ci.com/g/dmaicher/doctrine-test-bundle/badges/quality-score.png?b=master
[Master coverage image]: https://scrutinizer-ci.com/g/dmaicher/doctrine-test-bundle/badges/coverage.png?b=master

[Packagist link]: https://packagist.org/packages/dama/doctrine-test-bundle
[Master build link]: https://travis-ci.org/dmaicher/doctrine-test-bundle
[Master scrutinizer link]: https://scrutinizer-ci.com/g/dmaicher/doctrine-test-bundle/?branch=master
