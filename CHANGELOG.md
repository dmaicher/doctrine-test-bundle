# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

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
