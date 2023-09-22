tests/Functional/app/parameters.yml:
	cp tests/Functional/app/parameters.yml.dist tests/Functional/app/parameters.yml

test: tests/Functional/app/parameters.yml
	vendor/bin/phpunit -c tests/ tests/

test_phpunit_legacy: tests/Functional/app/parameters.yml
	vendor/bin/phpunit -c tests/phpunit.9.xml tests/

phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon -a vendor/autoload.php -l 5 src tests

behat:
	vendor/bin/behat -c tests/behat.yml -fprogress

build: test phpstan php_cs_fixer_check

php_cs_fixer_fix:
	vendor/bin/php-cs-fixer fix --config .php-cs-fixer.php src tests

php_cs_fixer_check:
	vendor/bin/php-cs-fixer fix --config .php-cs-fixer.php src tests --dry-run --diff
