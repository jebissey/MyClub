find ../ -path "*/vendor" -prune -o -name "*.php" -type f -exec wc -l {} + > LinesByFiles.txt

