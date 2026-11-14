#!/usr/bin/env bash
# Run PHPStan, then push the report to the local SonarQube server.
set -euo pipefail

# Repository root, whatever the current directory is
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Load the token from a private file instead of prompting every time
SONAR_TOKEN="$(<"$HOME/.config/sonar-token")"
export SONAR_TOKEN

# Start the container if it is not running
if ! docker ps --format '{{.Names}}' | grep -qx sonarqube; then
  docker start sonarqube
fi

# Wait until the server is ready
until curl -sf http://localhost:9000/api/system/status | grep -q '"status":"UP"'; do
  echo "Waiting for SonarQube..."
  sleep 5
done

# PHPStan runs from WebSite/ (vendor lives there) but writes its report to dev/
# It exits non-zero when it finds errors, which is expected here
(cd "$ROOT/WebSite" && vendor/bin/phpstan analyse \
  -c ../dev/phpstan.neon --error-format=json --no-progress \
  > "$ROOT/dev/phpstan-report.json") || true

# Generate coverage and test reports consumed by SonarQube
(cd "$ROOT/WebSite" && vendor/bin/phpunit -c ../dev/phpunit.xml \
  --coverage-clover ../dev/coverage.xml \
  --log-junit ../dev/junit.xml) || true

# Run from the repository root so sonar-project.properties is picked up
cd "$ROOT"
sonar-scanner