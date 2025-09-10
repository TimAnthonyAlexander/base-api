# Translation System Usage Guide

## Overview

BaseAPI now includes a comprehensive translation system that supports:
- File-based JSON translations
- HTTP endpoint for frontend integration
- Auto-translation with DeepL/OpenAI
- ICU MessageFormat for plurals and selects
- CLI tools for management
- ETag caching for performance

## Quick Start

### 1. Configuration

Add to your `.env` file:

```env
I18N_DEFAULT_LOCALE=en
I18N_LOCALES=en,de,fr
I18N_PROVIDER=deepl
DEEPL_API_KEY=your-deepl-key
```

### 2. Usage in Controllers

```php
use BaseApi\Support\I18n;

class UserController extends Controller
{
    public function get(): JsonResponse
    {
        $message = I18n::t('common.welcome_message', [
            'name' => $this->user->name,
            'count' => $this->user->unreadCount
        ]);
        
        return JsonResponse::ok(['message' => $message]);
    }
}
```

### 3. HTTP Endpoint Usage

Get all translations for German:
```
GET /i18n?lang=de
```

Get specific namespaces:
```
GET /i18n?lang=de&ns=common,emails
```

Response:
```json
{
    "data": {
        "lang": "de",
        "namespaces": ["common", "emails"],
        "tokens": {
            "common.ok": "OK",
            "common.items_count": "{count, plural, one {# Element} other {# Elemente}}"
        }
    }
}
```

### 4. CLI Commands

#### Scan for tokens in code
```bash
php bin/console i18n:scan --write
```

#### Add new language with auto-translation
```bash
php bin/console i18n:add-lang fr --auto
```

#### Fill missing translations
```bash
php bin/console i18n:fill --to=de,fr
```

#### Lint translation files
```bash
php bin/console i18n:lint --fail-on-orphans
```

#### Generate cache hashes
```bash
php bin/console i18n:hash --lang=de --ns=common
```

## Translation File Format

### File Structure
```
translations/
├── en/
│   ├── common.json
│   ├── emails.json
│   └── errors.json
└── de/
    ├── common.json
    ├── emails.json
    └── errors.json
```

### File Content
```json
{
  "__meta": {
    "needs_review": false,
    "last_sync": "2025-09-10T15:30:00Z"
  },
  "common.ok": "OK",
  "emails.welcome_subject": "Welcome, {name}!",
  "common.items_count": "{count, plural, one {# item} other {# items}}",
  "common.user_status": "{status, select, online {Online} offline {Offline} other {Unknown}}"
}
```

## Token Patterns

The system automatically scans for these patterns:
- `t('token.name')`
- `T::t('token.name')`
- `@t('token.name')` (in templates)
- `__('token.name')` (Laravel style)

## ICU MessageFormat

### Simple Variables
```
"Hello, {name}!"
```

### Plurals
```
"{count, plural, one {# item} other {# items}}"
```

### Selects
```
"{gender, select, male {He} female {She} other {They}} is online"
```

### Complex Example
```
"Welcome back, {name}! You have {count, plural, one {# new message} other {# new messages}}."
```

## Best Practices

1. **Token Naming**: Use namespace prefixes like `common.ok`, `emails.subject`
2. **Placeholders**: Keep them consistent across languages
3. **Plurals**: Always include 'other' case for ICU plurals
4. **Review**: Set `needs_review: true` for auto-translations
5. **Validation**: Run `i18n:lint` in your CI pipeline

## Error Handling

The system gracefully handles:
- Missing translations (falls back to default locale or token key)
- Invalid ICU format (logs error, uses simple replacement)
- Missing providers (auto-translation is skipped)
- Network failures (translation commands show warnings)

## Performance

- Translations are cached in memory per request
- File mtimes are checked for cache invalidation
- HTTP endpoint uses ETag headers for 304 responses
- Bundle generation is optimized for namespace filtering
