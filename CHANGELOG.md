# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [6.6.0]
### Changed
- dropped support for unmaintained Symfony versions 3.4 and 5.1
- fixed compatibility with DoctrineBundle 2.4+ and ORM 2.9+ by introducing PSR-6 compatible cache
- added dependency on `symfony/cache`

## [6.5.0]
### Changed
- add support for doctrine/dbal 3

## [6.4.0]
### Changed
- add support for php 8
- add Behat support
- drop support for unmaintained Symfony versions 4.3 and 5.0

## [6.3.0]
### Changed
- add support for enabling static transactional handling per doctrine dbal connection
- relaxed php requirement: allowing php 7.1 again

## [6.2.0]
### Changed
- drop support for unmaintained Symfony versions
- allow Symfony 5
- allow DoctrineBundle 2

## [6.1.0]
### Changed
- add typehints
- fix deprecations
- update docs

## [6.0.0]
### Changed
- drop support for PHP < 7.2
- drop support for PHPUnit 6
- drop support for Symfony 2
- bump requirement for `doctrine/dbal` to `~2.9`
- bump requirement for `doctrine/doctrine-bundle` to `~1.11`

## [5.0.0]
### Changed
- support PHPUnit 7
- drop support for PHPUnit 5
- drop support for PHP 5.6 and 7.0


## [4.0.0]
### Changed
- use only one listener class for both PHPUnit 5 and 6+.
- use savepoints for nested transactions if possible
- add functional tests


## [3.2.0]
### Changed
- decorate original doctrine connection factory service instead of replacing it
  

## [3.1.0]
### Changed
- only require `symfony/framework-bundle` instead of `symfony/symfony`


## [3.0.0]
### Changed
- made `phpunit/phpunit` a `dev` dependency only
- removed separation of two different branches/releases for PHPUnit < 6 (`1.x`) and >= 6 (`master`/`2.x`)
- renamed phpunit listener class

### Fixed
- compatibility with `symfony/phpunit-bridge` 
