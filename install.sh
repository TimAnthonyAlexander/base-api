#!/bin/bash

# BaseAPI One-Line Installer
# Usage: curl -sSL https://raw.githubusercontent.com/timanthonyalexander/base-api/main/install.sh | bash
# Or: bash <(curl -sSL https://raw.githubusercontent.com/timanthonyalexander/base-api/main/install.sh) [directory]

set -e

# Configuration
REPO_URL="https://github.com/timanthonyalexander/base-api.git"
PROJECT_NAME="base-api"
MIN_PHP_VERSION="8.4"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Helper functions
print_header() {
    echo -e "\n${PURPLE}╔═══════════════════════════════════════════════╗${NC}"
    echo -e "${PURPLE}║                                               ║${NC}"
    echo -e "${PURPLE}║            ${CYAN}BaseAPI Installer${PURPLE}                  ║${NC}"
    echo -e "${PURPLE}║                                               ║${NC}"
    echo -e "${PURPLE}║   ${YELLOW}A tiny, KISS-first PHP 8.4 framework${PURPLE}      ║${NC}"
    echo -e "${PURPLE}║        ${YELLOW}for building JSON-first APIs${PURPLE}          ║${NC}"
    echo -e "${PURPLE}║                                               ║${NC}"
    echo -e "${PURPLE}╚═══════════════════════════════════════════════╝${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1" >&2
}

check_command() {
    if command -v "$1" >/dev/null 2>&1; then
        return 0
    else
        return 1
    fi
}

version_compare() {
    printf '%s\n%s\n' "$1" "$2" | sort -V | head -n1
}

check_php_version() {
    if ! check_command php; then
        print_error "PHP is not installed. Please install PHP ${MIN_PHP_VERSION} or higher."
        echo -e "\n${CYAN}Installation guides:${NC}"
        echo "  • macOS: brew install php"
        echo "  • Ubuntu: sudo apt install php php-cli php-mbstring php-xml php-curl"
        echo "  • CentOS: sudo yum install php php-cli php-mbstring php-xml php-curl"
        return 1
    fi

    local current_version=$(php -r "echo PHP_VERSION;" 2>/dev/null)
    if [[ $(version_compare "$current_version" "$MIN_PHP_VERSION") != "$current_version" ]]; then
        print_error "PHP ${MIN_PHP_VERSION} or higher is required. Current version: ${current_version}"
        return 1
    fi

    print_success "PHP ${current_version} is installed"
    return 0
}

check_composer() {
    if ! check_command composer; then
        print_error "Composer is not installed. Please install Composer first."
        echo -e "\n${CYAN}Install Composer:${NC}"
        echo "  curl -sS https://getcomposer.org/installer | php"
        echo "  sudo mv composer.phar /usr/local/bin/composer"
        echo -e "\n${CYAN}Or visit:${NC} https://getcomposer.org/download/"
        return 1
    fi

    print_success "Composer is installed"
    return 0
}

check_git() {
    if ! check_command git; then
        print_error "Git is not installed. Please install Git first."
        echo -e "\n${CYAN}Installation guides:${NC}"
        echo "  • macOS: brew install git"
        echo "  • Ubuntu: sudo apt install git"
        echo "  • CentOS: sudo yum install git"
        return 1
    fi

    print_success "Git is installed"
    return 0
}

main() {
    print_header

    # Parse arguments
    local target_dir="${1:-$PROJECT_NAME}"
    
    print_info "Installing BaseAPI to: ${CYAN}${target_dir}${NC}"
    echo

    # Check system requirements
    print_info "Checking system requirements..."
    
    if ! check_php_version; then
        exit 1
    fi

    if ! check_composer; then
        exit 1
    fi

    if ! check_git; then
        exit 1
    fi

    echo

    # Check if directory already exists
    if [[ -d "$target_dir" ]]; then
        print_warning "Directory '$target_dir' already exists."
        read -p "Do you want to continue? This will remove the existing directory. [y/N]: " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_info "Installation cancelled."
            exit 0
        fi
        rm -rf "$target_dir"
        print_success "Existing directory removed"
    fi

    # Clone repository
    print_info "Cloning BaseAPI repository..."
    if git clone --quiet "$REPO_URL" "$target_dir"; then
        print_success "Repository cloned successfully"
    else
        print_error "Failed to clone repository"
        exit 1
    fi

    # Enter project directory
    cd "$target_dir"

    # Install dependencies
    print_info "Installing PHP dependencies..."
    if composer install --quiet --no-dev --optimize-autoloader; then
        print_success "Dependencies installed successfully"
    else
        print_error "Failed to install dependencies"
        exit 1
    fi

    # Setup environment file
    print_info "Setting up environment configuration..."
    if [[ -f ".env.example" ]]; then
        cp .env.example .env
        print_success "Environment file created (.env)"
    else
        # Create a basic .env file
        cat > .env << 'EOF'
# App Configuration
APP_NAME=BaseApi
APP_ENV=local
APP_DEBUG=true
APP_HOST=localhost
APP_PORT=8000

# CORS Configuration
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000

# Database Configuration (optional)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=baseapi
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
DB_PERSISTENT=false

# Rate Limiting
RATE_LIMIT_DIR=storage/ratelimits
APP_TRUST_PROXY=false

# Session Configuration
SESSION_SECURE=false
SESSION_HTTPONLY=true
SESSION_SAMESITE=Lax
EOF
        print_success "Environment file created (.env)"
    fi

    # Create storage directories
    print_info "Creating storage directories..."
    mkdir -p storage/{logs,ratelimits,uploads/private,uploads/public}
    print_success "Storage directories created"

    # Set permissions
    print_info "Setting permissions..."
    chmod -R 775 storage/
    print_success "Permissions set"

    # Test installation
    print_info "Testing installation..."
    if php -f public/index.php >/dev/null 2>&1; then
        print_success "Installation test passed"
    else
        print_warning "Installation test failed, but this might be normal without a web server"
    fi

    # Success message
    echo
    echo -e "${GREEN}🎉 BaseAPI has been installed successfully!${NC}"
    echo
    echo -e "${CYAN}Next steps:${NC}"
    echo -e "  1. ${YELLOW}cd $target_dir${NC}"
    echo -e "  2. ${YELLOW}php bin/console serve${NC}  ${BLUE}# Start development server${NC}"
    echo -e "  3. Visit ${YELLOW}http://localhost:8000/health${NC} to test"
    echo
    echo -e "${CYAN}Useful commands:${NC}"
    echo -e "  • ${YELLOW}php bin/console make:controller UserController${NC}  ${BLUE}# Generate controller${NC}"
    echo -e "  • ${YELLOW}php bin/console make:model User${NC}                 ${BLUE}# Generate model${NC}"
    echo -e "  • ${YELLOW}php bin/console types:generate${NC}                  ${BLUE}# Generate TypeScript types${NC}"
    echo
    echo -e "${CYAN}Configuration:${NC}"
    echo -e "  • Edit ${YELLOW}.env${NC} for your environment settings"
    echo -e "  • Edit ${YELLOW}routes/api.php${NC} to define your API routes"
    echo -e "  • See ${YELLOW}README.md${NC} for full documentation"
    echo
    echo -e "${PURPLE}Happy coding with BaseAPI! 🚀${NC}"
}

# Run main function with all arguments
main "$@"
