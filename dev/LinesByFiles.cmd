(
  echo "=== Détail par fichier ===";
  find ../WebSite/ -path "*/vendor" -prune -o -path "*/var" -prune -o \( -name "*.php" -o -name "*.latte" -o -name "*.js" \) -type f -exec wc -l {} +;
  echo -e "\n=== Total par type de fichier ===";
  echo "Fichiers PHP :";
  find ../WebSite/ -path "*/vendor" -prune -o -path "*/var" -prune -o -name "*.php" -type f -exec wc -l {} + | awk 'END {print $1}';
  echo "Fichiers Latte :";
  find ../WebSite/ -path "*/vendor" -prune -o -path "*/var" -prune -o -name "*.latte" -type f -exec wc -l {} + | awk 'END {print $1}';
  echo "Fichiers Javascript :";
  find ../WebSite/ -path "*/vendor" -prune -o -path "*/var" -prune -o -name "*.js" -type f -exec wc -l {} + | awk 'END {print $1}';
) > LinesByFiles.txt
