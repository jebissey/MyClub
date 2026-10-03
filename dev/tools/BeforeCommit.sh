#!/usr/bin/env bash

set -e

cd "$(dirname "$0")/../../WebSite"

print_step() {
echo >&2
echo "=============================================================================" >&2
echo -e "\033[1m  $1\033[0m" >&2
echo "=============================================================================" >&2
}

print_step "1/7 : phpcbf"
vendor/bin/phpcbf --standard=../dev/phpcs.xml --runtime-set ignore_non_auto_fixable_on_exit 1 app || true

print_step "2/7 : phpcs"
vendor/bin/phpcs --standard=../dev/phpcs.xml app

print_step "3/7 : phpstan"
vendor/bin/phpstan analyse -c ../dev/phpstan.neon

print_step "4/7 : checking unit tests"
php ../dev/test/CheckMissingTests.php

print_step "5/7 : phpunit"
vendor/bin/phpunit -c ../dev/phpunit.xml

print_step "6/7 : coding standards check"
php ../dev/test/CheckCodingStandards.php

print_step "7/7 : js cache busting check (|version)"
php ../dev/test/CheckJs.php

echo >&2
echo "==================================================" >&2
echo "  🎉 All checks passed, ready for commit." >&2
echo "==================================================" >&2
echo >&2