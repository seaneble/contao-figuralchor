#!/bin/bash
# Deploys seaneble/contao-figuralchor to the website checkout on this server.
# Run from anywhere on figuralchor-stuttgart.de.
set -euo pipefail

BUNDLE_DIR="$HOME/dev/contao-figuralchor"
WEBSITE_DIR="$HOME/htdocs/website"
COMPOSER="php8.4-cli $HOME/bin/composer.phar"
CONSOLE="php8.4-cli vendor/bin/contao-console"
HEALTH_URL="https://test.figuralchor-stuttgart.de/"

echo "==> Pulling latest bundle code"
cd "$BUNDLE_DIR"
git pull origin master

echo "==> Syncing theme CSS into files/theme/"
cp "$BUNDLE_DIR/assets/css/theme.css" "$WEBSITE_DIR/files/theme/theme.css"

cd "$WEBSITE_DIR"

echo "==> composer update seaneble/contao-figuralchor"
$COMPOSER update seaneble/contao-figuralchor --no-interaction

echo "==> Syncing file index"
$CONSOLE contao:filesync --no-interaction

echo "==> Clearing cache"
$CONSOLE cache:clear --no-interaction

echo "==> Checking for pending migrations"
$CONSOLE contao:migrate --dry-run --no-interaction

echo "==> Health check"
STATUS=$(curl -s -o /dev/null -w '%{http_code}' "$HEALTH_URL")
echo "HTTP $STATUS from $HEALTH_URL"
if [[ "$STATUS" != "200" && "$STATUS" != "302" ]]; then
    echo "!! Unexpected status code, investigate before considering this deploy done."
    exit 1
fi

echo "==> Deploy complete"
