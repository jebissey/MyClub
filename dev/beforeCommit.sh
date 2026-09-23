#!/usr/bin/env bash

set -e

cd "$(dirname "$0")/../WebSite"

print_step() {
  echo >&2
  echo "=============================================================================" >&2
  echo -e "\033[1m  $1\033[0m" >&2
  echo "=============================================================================" >&2
}

print_step "1/5 : phpcbf"
vendor/bin/phpcbf --runtime-set ignore_non_auto_fixable_on_exit 1 || true

print_step "2/5 : phpcs"
vendor/bin/phpcs

print_step "3/5 : phpstan"
vendor/bin/phpstan analyse

print_step "4/5 : checking unit tests"
php ../test/CheckMissingTests.php

print_step "5/5 : phpunit"
vendor/bin/phpunit

echo >&2
echo "==================================================" >&2
echo "  🎉 All checks passed, ready for commit." >&2
echo "==================================================" >&2
echo >&2