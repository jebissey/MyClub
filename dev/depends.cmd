#!/bin/bash

find ../WebSite/app -name "*.php" | while read file; do
  echo -n "$file depends on: "
  grep -oP '^use \K[^;]+' "$file" | tr '\n' ',' | sed 's/,$//'
  echo
done | sort