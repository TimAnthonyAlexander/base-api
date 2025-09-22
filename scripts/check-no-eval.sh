#!/bin/bash

# CI Guard Script: Check for eval() usage
# This script prevents eval() usage regressions in the codebase
# Exit code 0 = no eval found (success)
# Exit code 1 = eval found (failure)

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
SEARCH_DIRS="src tests"
EXCLUDE_PATTERNS="vendor node_modules .git"

echo "🔍 Checking for eval() usage in PHP files..."
echo "   Search directories: $SEARCH_DIRS"
echo "   Excluded patterns: $EXCLUDE_PATTERNS"
echo ""

# Initialize counters
EVAL_FOUND=0
FILES_CHECKED=0

# Function to check a single file
check_file() {
    local file="$1"
    FILES_CHECKED=$((FILES_CHECKED + 1))
    
    # List of test files that legitimately contain eval in test strings/assertions/comments
    # These are allowed to have eval in string literals but not actual eval() calls
    ALLOWED_TEST_FILES=(
        "tests/Console/Commands/TypesGenerateCommandTest.php"
        "tests/RateLimitMiddlewareTest.php"
    )
    
    # Check if this is an allowed test file
    local is_allowed_test=false
    for allowed_file in "${ALLOWED_TEST_FILES[@]}"; do
        if [[ "$file" == *"$allowed_file" ]]; then
            is_allowed_test=true
            break
        fi
    done
    
    if [ "$is_allowed_test" = true ]; then
        # For allowed test files, use a stricter check that excludes string literals and assertions
        # Look for eval not in quotes, comments, or assertion methods
        local eval_matches=$(grep -n -E '\beval\s*\(' "$file" 2>/dev/null | \
            grep -v -E '(//.*eval|/\*.*eval|\*.*eval|\s\*.*eval|'\''.*eval.*'\''|".*eval.*"|->assert.*eval|assert.*eval)' || true)
        
        if [[ -n "$eval_matches" ]]; then
            echo -e "${RED}❌ ACTUAL EVAL USAGE FOUND in: $file${NC}"
            echo "$eval_matches"
            EVAL_FOUND=$((EVAL_FOUND + 1))
            return 1
        fi
    else
        # For all other files (including other tests), be strict - no eval allowed anywhere
        local eval_matches=$(grep -n -E '\beval\s*\(' "$file" 2>/dev/null || true)
        if [[ -n "$eval_matches" ]]; then
            echo -e "${RED}❌ EVAL FOUND in: $file${NC}"
            echo "$eval_matches"
            EVAL_FOUND=$((EVAL_FOUND + 1))
            return 1
        fi
    fi
    
    return 0
}

# Find and check PHP files
for search_dir in $SEARCH_DIRS; do
    if [ ! -d "$search_dir" ]; then
        echo -e "${YELLOW}⚠️  Directory '$search_dir' not found, skipping...${NC}"
        continue
    fi
    
    echo "Checking directory: $search_dir"
    
    # Find PHP files, excluding specified patterns - avoid subshell by using process substitution
    while IFS= read -r -d '' file; do
        # Skip excluded patterns
        skip=false
        for pattern in $EXCLUDE_PATTERNS; do
            if [[ "$file" == *"$pattern"* ]]; then
                skip=true
                break
            fi
        done
        
        if [ "$skip" = false ]; then
            check_file "$file" || true  # Don't exit early, collect all violations
        fi
    done < <(find "$search_dir" -name "*.php" -type f -print0)
done

echo ""
echo "📊 Summary:"
echo "   Files checked: $FILES_CHECKED"

# Check if we found any eval usage
if [ $EVAL_FOUND -gt 0 ]; then
    echo -e "   ${RED}Eval violations found: $EVAL_FOUND${NC}"
    echo ""
    echo -e "${RED}💥 SECURITY VIOLATION: eval() usage detected!${NC}"
    echo ""
    echo "eval() poses significant security risks and should not be used."
    echo "Please refactor the code to use safer alternatives such as:"
    echo "  - File generation with validation"
    echo "  - Proper class loading mechanisms"
    echo "  - Configuration-based approaches"
    echo ""
    echo "For more information, see:"
    echo "  - https://www.php.net/manual/en/function.eval.php"
    echo "  - https://owasp.org/www-community/attacks/Code_Injection"
    echo ""
    exit 1
else
    echo -e "   ${GREEN}Eval violations found: 0${NC}"
    echo ""
    echo -e "${GREEN}✅ SUCCESS: No eval() usage found in codebase!${NC}"
    echo ""
    exit 0
fi
