#!/bin/bash
# CI check script to prevent direct $_ENV usage in framework code
# Usage: ./scripts/check-no-env.sh

set -e

echo "🔍 Checking for direct \$_ENV usage in src/..."

# Search for $_ENV in src/ directory, excluding specific allowed files
# App.php is allowed because it passes $_ENV to Config constructor
if grep -r '\$_ENV' src/ \
    --exclude-dir=vendor \
    --exclude="App.php" \
    | grep -v "// \$_ENV" \
    | grep -v "* \$_ENV"; then
    
    echo ""
    echo "❌ ERROR: Direct \$_ENV usage detected in src/"
    echo ""
    echo "Please use Config::get('dot.path', default) instead of \$_ENV['KEY']"
    echo ""
    echo "Examples:"
    echo "  ❌ \$_ENV['APP_DEBUG']"
    echo "  ✅ App::config('app.debug', false)"
    echo ""
    echo "  ❌ \$_ENV['DB_HOST']"
    echo "  ✅ App::config('database.host', '127.0.0.1')"
    echo ""
    exit 1
fi

echo "✅ No direct \$_ENV usage found in src/"
exit 0


