[![PHP Version](https://img.shields.io/packagist/php-v/dama/doctrine-test-bundle)](https://packagist.org/packages/dama/doctrine-test-bundle)
[![Stable release](https://img.shields.io/packagist/v/dama/doctrine-test-bundle)](https://packagist.org/packages/dama/doctrine-test-bundle)

### What does it do? :blush:

This bundle provides features that help you run your Symfony-framework-based App's testsuite more efficiently with isolated tests.

It provides a `StaticDriver` that will wrap your originally configured `Driver` class (like `DBAL\Driver\PDOMysql\Driver`) and keeps a database connection statically in the current php process.

With the help of a PHPUnit extension class it will begin a transaction before every testcase and roll it back again after the test finished for all configured DBAL connections. This results in a performance boost as there is no need to rebuild the schema, import a backup SQL dump or re-insert fixtures before every testcase. As long as you avoid issuing DDL queries that might result in implicit transaction commits (Like `ALTER TABLE`, `DROP TABLE` etc; see https://wiki.postgresql.org/wiki/Transactional_DDL_in_PostgreSQL:_A_Competitive_Analysis) your tests will be isolated and all see the same database state.

It also includes a `StaticArrayCache` / `Psr6StaticArrayCache` that will be automatically configured as meta data & query cache for all EntityManagers. This improved the speed and memory usage for my testsuites dramatically! This is especially beneficial if you have a lot of tests that boot kernels (like Controller tests or ContainerAware tests) and use Doctrine entities.

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
    
#### Using the Bundle with PHPUnit

1. Add the Extension to your PHPUnit XML config

   - PHPUnit 8 or 9:

       ```xml
       <phpunit>
           ...
           <extensions>
               <extension class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension" />
           </extensions>
       </phpunit>
       ```
   - PHPUnit 10+:

      ```xml
      <phpunit>
          ...
          <extensions>
              <bootstrap class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension" />
          </extensions>
      </phpunit>
      ```
    
2. Make sure you also have `phpunit/phpunit` available as a `dev` dependency (**versions 8, 9 and 10 are supported with the built-in extension**) to run your tests. 
   Alternatively this bundle is also compatible with `symfony/phpunit-bridge` and its `simple-phpunit` script. 
   (Note: you may need to make sure the phpunit-bridge requires the correct PHPUnit 8+ Version using the environment variable `SYMFONY_PHPUNIT_VERSION`). 

3. That's it! From now on whatever changes you do to the database within each single testcase (be it a `WebTestCase` or a `KernelTestCase` or any custom test) are automatically rolled back for you :blush:

#### Using the Bundle with Behat

Enable the extension in your Behat config (e.g. `behat.yml`)

```yaml
default:
   # ...
   extensions:
       DAMA\DoctrineTestBundle\Behat\ServiceContainer\DoctrineExtension: ~
```

That's it! From now on whatever changes you do to the database within each scenario are automatically rolled back for you.

Please note that this is only works if the tests are executed in the same process as Behat. This means it cannot work when using e.g. Selenium to call your application. 
    
### Configuration

The bundle exposes a configuration that looks like this by default:
    
```yaml
dama_doctrine_test:
    enable_static_connection: true
    enable_static_meta_data_cache: true
    enable_static_query_cache: true
```

Setting `enable_static_connection: true` means it will enable it for all configured doctrine dbal connections.

You can selectively only enable it for some connections if required:

```yaml
dama_doctrine_test:
    enable_static_connection:
        connection_a: true
```

### Example

An example usage can be seen within the functional tests included in this bundle: https://github.com/dmaicher/doctrine-test-bundle/tree/master/tests

- initial database bootstrap is done using PHPUnit bootstrap file: https://github.com/dmaicher/doctrine-test-bundle/blob/master/tests/bootstrap.php
- several tests that make sure any changes from previous tests are rolled back: https://github.com/dmaicher/doctrine-test-bundle/blob/master/tests/Functional/PhpunitTest.php

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
