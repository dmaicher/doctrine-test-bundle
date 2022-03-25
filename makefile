composer.phar:
	curl -s https://getcomposer.org/installer | php
	php composer.phar install --prefer-dist -o --dev

tests/Functional/app/parameters.yml:
	cp tests/Functional/app/parameters.yml.dist tests/Functional/app/parameters.yml

test: tests/Functional/app/parameters.yml
	vendor/bin/phpunit -c tests/ tests/

test_phpunit_7: tests/Functional/app/parameters.yml
	vendor/bin/phpunit -c tests/phpunit7.xml tests/

phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon -a vendor/autoload.php -l 5 src

behat:
	vendor/bin/behat -c tests/behat.yml -fprogress

build: composer.phar test phpstan php_cs_fixer_check

php_cs_fixer_fix: php-cs-fixer.phar
	./php-cs-fixer.phar fix --config .php-cs-fixer.php src tests

php_cs_fixer_check: php-cs-fixer.phar
	./php-cs-fixer.phar fix --config .php-cs-fixer.php src tests --dry-run --diff

php-cs-fixer.phar:
	wget https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases/download/v3.8.0/php-cs-fixer.phar && chmod 777 php-cs-fixer.phar
