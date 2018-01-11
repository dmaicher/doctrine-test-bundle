SHELL = /bin/bash

composer.phar:
	curl -s https://getcomposer.org/installer | php
	php composer.phar install --prefer-dist -o --dev

.PHONY: test
test:
	vendor/bin/phpunit -c tests/ tests/

.PHONY: phpstan
phpstan: phpstan.phar
	# Run PHPStan only for PHP version >=7.0
	php -r 'exit(PHP_VERSION_ID >= 70000 ? 1 : 0);' || ./phpstan.phar analyse -c phpstan.neon -a vendor/autoload.php -l 7 src

phpstan.phar:
	wget https://raw.githubusercontent.com/phpstan/phpstan-shim/0.9.1/phpstan.phar && chmod 777 phpstan.phar

.PHONY: build
build: composer.phar test phpstan
