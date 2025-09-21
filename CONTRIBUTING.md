# Contributing to BaseAPI

Thank you for your interest in contributing to BaseAPI! We welcome contributions from the community and are pleased to have you join us.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [How to Contribute](#how-to-contribute)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Documentation](#documentation)
- [Issue Reporting](#issue-reporting)
- [Feature Requests](#feature-requests)

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

**Our Standards:**
- Be respectful and inclusive
- Focus on what is best for the community
- Show empathy towards other community members
- Be collaborative and constructive in discussions
- Accept constructive criticism gracefully

## Getting Started

### Prerequisites

- PHP 8.4+ or higher
- Composer
- Git
- One of the supported databases (MySQL, PostgreSQL, SQLite)

### Understanding BaseAPI Architecture

BaseAPI is a **framework package** that provides the core functionality. It's designed to be used with the **BaseAPI Template** project:

- **BaseAPI Core** (`timanthonyalexander/base-api`) - The framework package (this repository)
- **BaseAPI Template** (`baseapi/baseapi-template`) - The project template that users create new projects from

Users create new projects with:
```bash
composer create-project baseapi/baseapi-template my-api
```

This installs BaseAPI as a dependency and provides the application structure (controllers, models, routes, config).

### Development Setup

#### For Framework Development (BaseAPI Core)

1. **Fork the BaseAPI repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/base-api.git
   cd base-api
   ```

3. **Install dependencies**:
   ```bash
   composer install
   ```

4. **Run tests** to ensure everything works:
   ```bash
   php vendor/bin/phpunit
   ```

5. **Create a test project** to test your changes:
   ```bash
   cd ..
   composer create-project baseapi/baseapi-template test-project
   cd test-project
   ```

6. **Link your local BaseAPI development version**:
   ```bash
   # In your test project, modify composer.json to use your local development version
   # Add this to composer.json repositories section:
   {
       "type": "path",
       "url": "../base-api"
   }
   
   # Then require your local version
   composer require timanthonyalexander/base-api:dev-main
   ```

7. **Create a new branch** for your feature or bugfix:
   ```bash
   cd ../base-api  # Back to your BaseAPI development directory
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/issue-description
   ```

#### For Testing Your Changes

After making changes to BaseAPI core:

1. **Update your test project**:
   ```bash
   cd ../test-project
   composer update timanthonyalexander/base-api
   ```

2. **Test the functionality**:
   ```bash
   ./mason serve
   # Test your changes in the running application
   ```

3. **Run BaseAPI core tests**:
   ```bash
   cd ../base-api
   php vendor/bin/phpunit
   ```

## How to Contribute

### Types of Contributions

BaseAPI accepts several types of contributions:

#### Framework Core Contributions
- **Bug fixes** - Fix issues in the core framework
- **Feature development** - Add new framework functionality (middleware, database features, CLI commands)
- **Database drivers** - Add support for new databases (like we recently added PostgreSQL)
- **Performance improvements** - Optimize framework performance
- **Security enhancements** - Improve framework security features

#### Documentation & Examples
- **Documentation** - Improve setup guides, API docs, and examples
- **Testing** - Add or improve test coverage for framework features
- **Setup guides** - Create database setup guides (like `examples/postgresql-setup.md`)

#### Template Project Contributions
For contributions to the project template (the structure users get when they run `composer create-project`), please contribute to the [BaseAPI Template repository](https://github.com/baseapi/baseapi-template).

#### What NOT to Contribute Here
- Application-specific controllers or models (those belong in individual projects)
- Project-specific configuration (those belong in the template or individual projects)
- Business logic implementations (those belong in user projects)

### Contribution Workflow

1. **Check existing issues** to see if your contribution is already being worked on
2. **Create an issue** for new features or significant changes to discuss the approach
3. **Fork and create a branch** as described in Development Setup
4. **Make your changes** following our coding standards
5. **Add tests** for your changes
6. **Update documentation** if needed
7. **Submit a pull request**

## Pull Request Process

### Before Submitting

- [ ] Ensure your code follows our coding standards
- [ ] Add or update tests for your changes
- [ ] Update documentation if needed
- [ ] Run the full test suite and ensure all tests pass
- [ ] Check that your changes don't break existing functionality
- [ ] Rebase your branch on the latest main branch

### Pull Request Guidelines

1. **Use a clear and descriptive title**
   - Good: "Add PostgreSQL JSONB support for model attributes"
   - Bad: "Fix stuff"

2. **Provide a detailed description** including:
   - What changes you made and why
   - Any breaking changes
   - How to test the changes
   - Screenshots for UI changes (if applicable)

3. **Reference related issues** using keywords like "Fixes #123" or "Closes #456"

4. **Keep pull requests focused** - one feature or fix per PR

5. **Update the changelog** if your change affects users

### Review Process

- All pull requests require at least one review from a maintainer
- We may request changes or ask questions
- Once approved, a maintainer will merge your PR
- We aim to review PRs within 48-72 hours

## Coding Standards

### PHP Standards

We follow PSR-12 coding standards with some additional conventions:

#### Code Style

```php
<?php

namespace BaseApi\Example;

use BaseApi\Models\BaseModel;
use BaseApi\Http\JsonResponse;

class ExampleController extends Controller
{
    public function index(): JsonResponse
    {
        $items = ExampleModel::all();
        
        return JsonResponse::ok([
            'data' => $items,
            'count' => count($items)
        ]);
    }
    
    private function validateInput(array $data): bool
    {
        return isset($data['required_field']) 
            && !empty($data['required_field']);
    }
}
```

#### Naming Conventions

- **Classes**: PascalCase (`UserController`, `DatabaseDriver`)
- **Methods**: camelCase (`getUserById`, `createConnection`)
- **Variables**: camelCase (`$userId`, `$connectionConfig`)
- **Constants**: SCREAMING_SNAKE_CASE (`MAX_RETRY_ATTEMPTS`)
- **Database tables**: snake_case (`user_profiles`, `order_items`)
- **Database columns**: snake_case (`created_at`, `user_id`)

#### Documentation

- Add PHPDoc comments for all public methods
- Include parameter and return type documentation
- Add class-level documentation for complex classes

```php
/**
 * Handles user authentication and session management
 */
class AuthController extends Controller
{
    /**
     * Authenticate user with email and password
     *
     * @param string $email User's email address
     * @param string $password User's password
     * @return JsonResponse Authentication result
     * @throws AuthenticationException When credentials are invalid
     */
    public function login(string $email, string $password): JsonResponse
    {
        // Implementation
    }
}
```

### Database Standards

- Use migrations for all database changes
- Follow naming conventions for tables and columns
- Add proper indexes for performance
- Include foreign key constraints where appropriate
- Use appropriate data types for each database system

## Testing Guidelines

### Test Requirements

- All new features must include tests
- Bug fixes should include regression tests
- Aim for high test coverage (>80%)
- Tests should be fast and reliable

### Test Structure

```php
<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use BaseApi\Example\ExampleClass;

class ExampleTest extends TestCase
{
    private ExampleClass $example;
    
    protected function setUp(): void
    {
        $this->example = new ExampleClass();
    }
    
    public function testExampleMethod(): void
    {
        $result = $this->example->doSomething('input');
        
        $this->assertEquals('expected', $result);
        $this->assertInstanceOf(SomeClass::class, $result);
    }
    
    public function testExampleMethodWithInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->example->doSomething('');
    }
}
```

### Running Tests

```bash
# Run all tests
php vendor/bin/phpunit

# Run specific test file
php vendor/bin/phpunit tests/ExampleTest.php

# Run tests with coverage (requires Xdebug)
php vendor/bin/phpunit --coverage-html coverage/
```

### Test Categories

- **Unit tests** - Test individual classes and methods
- **Integration tests** - Test component interactions
- **Database tests** - Test database operations and migrations
- **HTTP tests** - Test API endpoints and responses

## Documentation

### Types of Documentation

- **Code comments** - Inline documentation for complex logic
- **API documentation** - Generated from code annotations
- **User guides** - Setup and usage instructions
- **Examples** - Practical implementation examples

### Documentation Standards

- Write clear, concise documentation
- Include code examples where helpful
- Keep documentation up-to-date with code changes
- Use proper markdown formatting

### Updating Documentation

When making changes that affect users:

1. Update relevant documentation files
2. Add examples if introducing new features
3. Update the README if needed
4. Consider adding entries to examples/ directory

## Issue Reporting

### Before Creating an Issue

- Search existing issues to avoid duplicates
- Check if the issue exists in the latest version
- Try to reproduce the issue with minimal code

### Bug Reports

Include the following information:

- **BaseAPI version** (check `composer show timanthonyalexander/base-api`)
- **BaseAPI Template version** (if using template project)
- **PHP version**
- **Operating system**
- **Database type and version**
- **Steps to reproduce** (preferably in a fresh template project)
- **Expected behavior**
- **Actual behavior**
- **Error messages or logs**
- **Minimal code example**

### Bug Report Template

```markdown
**BaseAPI Version:** 0.3.11 (from `composer show timanthonyalexander/base-api`)
**Template Version:** 1.0.0 (if applicable)
**PHP Version:** 8.4.0
**OS:** macOS 14.0
**Database:** PostgreSQL 15.2

**Description:**
Brief description of the issue

**Steps to Reproduce:**
1. Create new project: `composer create-project baseapi/baseapi-template test-project`
2. Step two
3. Step three

**Expected Behavior:**
What should happen

**Actual Behavior:**
What actually happens

**Error Message:**
```
Error message here
```

**Code Example:**
```php
// Minimal code to reproduce the issue
// Include relevant model/controller code if applicable
```

**Project Structure:**
- [ ] Issue occurs in fresh template project
- [ ] Issue occurs in existing project
- [ ] Issue is in BaseAPI core functionality
- [ ] Issue is in template-specific code
```

## Feature Requests

### Before Requesting a Feature

- Check if the feature already exists
- Search existing feature requests
- Consider if the feature fits BaseAPI's philosophy of simplicity

### Feature Request Guidelines

- Explain the use case and problem you're trying to solve
- Describe the proposed solution
- Consider alternative solutions
- Discuss potential impact on existing functionality
- Provide examples of how the feature would be used

### Feature Request Template

```markdown
**Is your feature request related to a problem?**
A clear description of the problem you're trying to solve.

**Describe the solution you'd like**
A clear description of what you want to happen.

**Describe alternatives you've considered**
Alternative solutions or features you've considered.

**Use Case Examples**
Provide examples of how this feature would be used.

**Additional Context**
Any other context, screenshots, or examples.
```

## Development Guidelines

### Architecture Principles

BaseAPI follows these principles:

- **KISS (Keep It Simple, Stupid)** - Favor simplicity over complexity
- **Convention over Configuration** - Sensible defaults with minimal setup
- **Performance First** - Optimize for speed and efficiency
- **Developer Experience** - Make common tasks easy and intuitive
- **Backward Compatibility** - Avoid breaking changes when possible

### Adding New Features

When adding new features to BaseAPI core:

1. **Start small** - Implement the minimal viable version first
2. **Follow existing patterns** - Look at how similar features are implemented
3. **Consider all database drivers** - Ensure compatibility across MySQL, SQLite, and PostgreSQL
4. **Test with template project** - Always test your changes in an actual BaseAPI project created from the template
5. **Add comprehensive tests** - Cover happy path, edge cases, and error conditions
6. **Update documentation** - Include examples and configuration options
7. **Consider backward compatibility** - BaseAPI is used by many projects via the template

### Database Driver Development

When adding new database drivers:

1. Implement the `DatabaseDriverInterface`
2. Add comprehensive type mapping
3. Handle database-specific SQL syntax
4. Include introspection methods for migrations
5. Add thorough test coverage
6. Create setup documentation with examples

### Performance Considerations

- Profile your changes to ensure they don't degrade performance
- Use appropriate data structures and algorithms
- Minimize database queries
- Cache expensive operations when appropriate
- Consider memory usage for large datasets

## Release Process

### Versioning

BaseAPI follows [Semantic Versioning](https://semver.org/):

- **MAJOR** version for incompatible API changes
- **MINOR** version for backward-compatible functionality additions
- **PATCH** version for backward-compatible bug fixes

### Changelog

All notable changes are documented in CHANGELOG.md following the [Keep a Changelog](https://keepachangelog.com/) format.

## Getting Help

If you need help with contributing:

- Check existing documentation and examples
- Search through existing issues and discussions
- Create a new issue with the "question" label
- Reach out to maintainers for guidance

## Recognition

Contributors are recognized in several ways:

- Listed in the project's contributors
- Mentioned in release notes for significant contributions
- Invited to become maintainers for sustained contributions

## License

By contributing to BaseAPI, you agree that your contributions will be licensed under the same [MIT License](LICENSE) that covers the project.

---

Thank you for contributing to BaseAPI! Your efforts help make this framework better for everyone. 🚀
