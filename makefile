composer.phar:
	curl -s https://getcomposer.org/installer | php
	php composer.phar install --prefer-dist -o --dev

tests/Functional/parameters.yml:
	cp tests/Functional/parameters.yml.dist tests/Functional/parameters.yml

test: tests/Functional/parameters.yml
	vendor/bin/phpunit -c tests/ tests/

test_phpunit_7: tests/Functional/parameters.yml
	vendor/bin/phpunit -c tests/phpunit7.xml tests/

phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon -a vendor/autoload.php -l 5 src

build: composer.phar test phpstan php_cs_fixer_check

php_cs_fixer_fix: php-cs-fixer.phar
	./php-cs-fixer.phar fix --config .php_cs src tests

php_cs_fixer_check: php-cs-fixer.phar
	./php-cs-fixer.phar fix --config .php_cs src tests --dry-run --diff --diff-format=udiff

php-cs-fixer.phar:
	wget https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases/download/v2.16.6/php-cs-fixer.phar && chmod 777 php-cs-fixer.phar
