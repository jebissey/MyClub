#!/bin/bash

find ../WebSite/app -name "*.php" -exec grep -l "use " {} \; | xargs -n1 php -r 'echo $argv[1]." depends on: "; preg_match_all("/use (.*?);/", file_get_contents($argv[1]), $m); echo implode(", ", $m[1]).PHP_EOL;'|sort
