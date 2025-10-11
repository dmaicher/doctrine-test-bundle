test:
	vendor/bin/phpunit

phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon -a vendor/autoload.php -l 8 src
	vendor/bin/phpstan analyse -c phpstan.neon -a vendor/autoload.php -l 5 tests

behat:
	vendor/bin/behat -c tests/behat.yml -fprogress

build: test phpstan php_cs_fixer_check

php_cs_fixer_fix:
	vendor/bin/php-cs-fixer fix --config .php-cs-fixer.php src tests

php_cs_fixer_check:
	vendor/bin/php-cs-fixer fix --config .php-cs-fixer.php src tests --dry-run --diff
