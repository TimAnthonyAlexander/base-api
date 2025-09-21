# Rector Integration

This framework library uses [Rector](https://getrector.com/) to maintain PHP 8.4+ compatibility and apply automated refactoring for code quality improvements.

## Configuration

The Rector configuration is conservative and library-safe to ensure backward compatibility:

- **Language Level**: PHP 8.4 features and modernizations
- **Quality Sets**: Code quality, type declarations, dead code removal, early returns
- **Standards**: PSR-4, coding style improvements
- **Testing**: PHPUnit 11.x modernizations

### Excluded Areas

- Dynamic container classes that rely on reflection
- Base model classes that might use magic methods
- Areas where constructor property promotion could break serialization

## Usage

### Local Development

```bash
# Check for potential changes (dry-run, recommended)
composer rector

# Apply all changes
composer rector:fix
```

### CI Integration

The CI pipeline automatically runs Rector checks:

```bash
# CI-friendly output
composer rector:ci
```

If Rector finds code that needs updating, the CI will:
1. Fail the build
2. Generate a diff artifact showing required changes
3. Upload the artifact for review

## Best Practices

1. **Always run dry-run first**: Use `composer rector` before `composer rector:fix`
2. **Review changes carefully**: Rector changes can be extensive, especially for constructor property promotion
3. **Commit in logical chunks**: For large refactors, consider applying changes in phases
4. **Test after changes**: Run your test suite after applying Rector changes

## Rollout Strategy

This library follows a conservative rollout:

1. **Phase 1**: Safe transformations (dead code, early returns, imports)
2. **Phase 2**: Type declarations and readonly where safe
3. **Phase 3**: Constructor property promotion in specific namespaces (DTOs, value objects)

## Configuration Details

See `rector.php` for the complete configuration. The setup prioritizes:

- **Safety**: No risky transforms that could break public APIs
- **Compatibility**: Maintains backward compatibility for library consumers
- **Performance**: Parallel processing with caching enabled
