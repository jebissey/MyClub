find ../ -path "*/vendor" -prune -o -path "*/var" -prune -o  -name "*.php" -type f -exec wc -l {} + > LinesByFiles.txt

