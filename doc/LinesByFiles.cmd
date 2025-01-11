find ../MyClub/ -path "*/lib/jpgraph" -prune -o -path "*/lib/PhpMailer" -prune -o -name "*.php" -type f -exec wc -l {} + > LinesByFiles.txt

