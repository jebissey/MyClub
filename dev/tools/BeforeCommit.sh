#!/usr/bin/env bash

set -e

cd "$(dirname "$0")/../../WebSite"

print_step() {
echo >&2
echo "=============================================================================" >&2
echo -e "\033[1m  $1\033[0m" >&2
echo "=============================================================================" >&2
}

print_step "1/6 : phpcbf"
vendor/bin/phpcbf --standard=../dev/phpcs.xml --runtime-set ignore_non_auto_fixable_on_exit 1 app || true

print_step "2/6 : phpcs"
vendor/bin/phpcs --standard=../dev/phpcs.xml app

print_step "3/6 : phpstan"
vendor/bin/phpstan analyse -c ../dev/phpstan.neon

print_step "4/6 : checking unit tests"
php ../dev/test/CheckMissingTests.php

print_step "5/6 : phpunit"
vendor/bin/phpunit -c ../dev/phpunit.xml

print_step "6/6 : coding standards check"
php ../dev/test/CheckCodingStandards.php

echo >&2
echo "==================================================" >&2
echo "  🎉 All checks passed, ready for commit." >&2
echo "==================================================" >&2
echo >&2