
import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function Internationalization() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Internationalization (I18n)
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                BaseAPI's comprehensive internationalization system with automated translation tools
            </Typography>

            <Typography>
                BaseAPI provides a complete internationalization system that supports multiple languages,
                automatic translation scanning, AI-powered translation filling, and ICU message formatting
                for complex pluralization and formatting rules.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                BaseAPI's I18n system uses token-based translations with namespace support and includes
                CLI tools for automated translation management.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Basic Usage
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Support\\I18n;

// Simple translation
echo I18n::t('welcome_message'); // "Welcome to BaseAPI"

// Translation with parameters
echo I18n::t('hello_user', ['name' => 'John']); // "Hello, John!"

// Override locale
echo I18n::t('goodbye', [], 'es'); // "¡Adiós!"

// Check if translation exists
if (I18n::has('optional_message')) {
    echo I18n::t('optional_message');
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                CLI Commands
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="i18n:scan"
                        secondary="Scan codebase for translation tokens and update translation files"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="i18n:add-lang <locale>"
                        secondary="Add a new language to the project"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="i18n:fill"
                        secondary="Fill missing translations using AI providers (OpenAI/DeepL)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="i18n:lint"
                        secondary="Validate translation files for syntax and completeness"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Complete Workflow Guide
            </Typography>

            <Typography>
                This section provides concrete, step-by-step workflows for common internationalization scenarios.
            </Typography>

            {/* Scenario 1: New Project Setup */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Scenario 1: Setting up I18n for a New Project
            </Typography>

            <Typography>
                Starting from scratch with a new BaseAPI project that needs multiple languages:
            </Typography>

            <CodeBlock language="bash" code={`# Step 1: Configure default language in config/i18n.php
# Default configuration supports English ('en') out of the box

# Step 2: Add translation tokens to your controllers
# app/Controllers/AuthController.php`} />

            <CodeBlock language="php" code={`<?php
namespace App\\Controllers;

use BaseApi\\Controllers\\Controller;
use BaseApi\\Http\\JsonResponse;
use BaseApi\\Support\\I18n;

class AuthController extends Controller
{
    public string $email = '';
    public string $password = '';
    
    public function post(): JsonResponse
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);
        
        // Use I18n tokens for user-facing messages
        $user = User::where('email', $this->email)->first();
        
        if (!$user || !password_verify($this->password, $user->password)) {
            return JsonResponse::unauthorized([
                'message' => I18n::t('auth.invalid_credentials'),
                'error' => 'login_failed'
            ]);
        }
        
        return JsonResponse::ok([
            'message' => I18n::t('auth.login_success', ['name' => $user->name]),
            'token' => $this->generateToken($user)
        ]);
    }
}`} />

            <CodeBlock language="bash" code={`# Step 3: Scan your codebase to extract translation tokens
php bin/console i18n:scan --update

# This creates/updates translations/en/auth.json with:
{
    "invalid_credentials": "Invalid email or password",
    "login_success": "Welcome back, {name}!"
}`} />

            <CodeBlock language="bash" code={`# Step 4: Add additional languages
php bin/console i18n:add-lang fr --copy-from=en
php bin/console i18n:add-lang es --copy-from=en

# Step 5: Use AI to fill missing translations
php bin/console i18n:fill --provider=openai --locale=fr
php bin/console i18n:fill --provider=openai --locale=es

# Step 6: Validate all translations
php bin/console i18n:lint --fix`} />

            {/* Scenario 2: Adding New Features */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Scenario 2: Adding New Features with I18n
            </Typography>

            <Typography>
                You're adding a new user profile feature and need to internationalize it:
            </Typography>

            <CodeBlock language="php" code={`<?php
// app/Controllers/ProfileController.php

class ProfileController extends Controller
{
    public string $id = '';
    public string $bio = '';
    public string $location = '';
    
    public function put(): JsonResponse
    {
        $user = User::find($this->id);
        if (!$user) {
            return JsonResponse::notFound([
                'message' => I18n::t('user.not_found')
            ]);
        }
        
        $this->validate([
            'bio' => 'string|max:500',
            'location' => 'string|max:100',
        ], [
            'bio.max' => I18n::t('validation.bio_too_long'),
            'location.max' => I18n::t('validation.location_too_long'),
        ]);
        
        $user->bio = $this->bio;
        $user->location = $this->location;
        $user->save();
        
        return JsonResponse::ok([
            'message' => I18n::t('user.profile_updated'),
            'user' => $user->jsonSerialize()
        ]);
    }
}`} />

            <CodeBlock language="bash" code={`# Workflow for new feature:

# 1. Write your code with I18n tokens (shown above)

# 2. Scan for new tokens and update base language
php bin/console i18n:scan --update

# 3. Check what tokens were added
cat translations/en/user.json
cat translations/en/validation.json

# 4. Fill missing translations for all languages
php bin/console i18n:fill --provider=deepl --all

# 5. Review and refine translations manually
# Edit translations/fr/user.json, translations/es/user.json, etc.

# 6. Test translations
curl -H "Accept-Language: fr" http://localhost:7879/users/123/profile
curl -H "Accept-Language: es" http://localhost:7879/users/123/profile

# 7. Validate everything is correct
php bin/console i18n:lint`} />

            {/* Scenario 3: Managing Large Translation Projects */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Scenario 3: Managing Large Translation Projects
            </Typography>

            <Typography>
                For larger projects with many translators and languages:
            </Typography>

            <CodeBlock language="bash" code={`# 1. Set up systematic token organization
# Use namespaced tokens for different areas:
# - auth.* for authentication
# - user.* for user management  
# - order.* for e-commerce functionality
# - admin.* for admin interface

# 2. Export translations for external translation services
php bin/console i18n:export --format=csv --locale=en > translations_en.csv
php bin/console i18n:export --format=po --locale=en > translations_en.po

# 3. Send files to translators, receive back translated versions

# 4. Import translated files
php bin/console i18n:import --format=csv --locale=fr translations_fr.csv
php bin/console i18n:import --format=po --locale=de translations_de.po

# 5. Use AI to fill any missing translations
php bin/console i18n:fill --provider=deepl --missing-only

# 6. Validate and lint all translations
php bin/console i18n:lint --strict

# 7. Generate translation reports
php bin/console i18n:stats --detailed`} />

            {/* Translation File Examples */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Translation File Structure
            </Typography>

            <Typography>
                Here's how your translation files should be organized:
            </Typography>

            <CodeBlock language="json" title="translations/en/auth.json" code={`{
  "login_success": "Welcome back, {name}!",
  "login_failed": "Invalid email or password",
  "logout_success": "You have been logged out successfully",
  "signup_success": "Account created successfully! Please verify your email.",
  "password_reset_sent": "Password reset instructions sent to {email}",
  "password_reset_success": "Your password has been reset successfully",
  "email_verification_sent": "Verification email sent to {email}",
  "email_verified": "Email address verified successfully"
}`} />

            <CodeBlock language="json" title="translations/fr/auth.json" code={`{
  "login_success": "Bon retour, {name} !",
  "login_failed": "Email ou mot de passe invalide",
  "logout_success": "Vous avez été déconnecté avec succès",
  "signup_success": "Compte créé avec succès ! Veuillez vérifier votre email.",
  "password_reset_sent": "Instructions de réinitialisation envoyées à {email}",
  "password_reset_success": "Votre mot de passe a été réinitialisé avec succès",
  "email_verification_sent": "Email de vérification envoyé à {email}",
  "email_verified": "Adresse email vérifiée avec succès"
}`} />

            <CodeBlock language="json" title="translations/en/validation.json" code={`{
  "required": "The {field} field is required",
  "email": "Please enter a valid email address",
  "min": "The {field} must be at least {min} characters",
  "max": "The {field} must not exceed {max} characters",
  "unique": "This {field} is already taken",
  "confirmed": "The {field} confirmation does not match",
  "numeric": "The {field} must be a number",
  "integer": "The {field} must be an integer",
  "boolean": "The {field} must be true or false"
}`} />

            {/* Advanced Usage */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Advanced Usage Patterns
            </Typography>

            <CodeBlock language="php" code={`<?php

// Pluralization with ICU message format
I18n::t('order.items_count', ['count' => $count]);
// Translation: "{count, plural, =0{No items} =1{One item} other{# items}}"

// Conditional translations
I18n::t('user.status', [
    'status' => $user->isActive() ? 'active' : 'inactive',
    'name' => $user->name
]);
// Translation: "{name} is {status, select, active{currently online} inactive{offline} other{unknown}}"

// Date and number formatting
I18n::t('order.created', [
    'date' => $order->created_at->format('Y-m-d'),
    'total' => number_format($order->total, 2)
]);
// Translation: "Order created on {date} for $25.99"

// Nested translations with fallbacks
I18n::t('errors.database.connection', [], 'en'); // Force English
I18n::t('missing.translation', [], null, 'Default fallback text');

// Context-aware translations
I18n::tContext('navigation', 'home'); // Different from I18n::t('home')
I18n::tContext('form', 'submit');    // Context-specific translation`} />

            {/* Testing Translations */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Testing Your Translations
            </Typography>

            <CodeBlock language="bash" code={`# Test specific language via HTTP headers
curl -H "Accept-Language: fr" http://localhost:7879/auth/login \\
  -X POST -d '{"email":"test","password":"wrong"}'

# Test with query parameter  
curl "http://localhost:7879/auth/login?lang=es" \\
  -X POST -d '{"email":"test","password":"wrong"}'

# Test token resolution
php bin/console i18n:test auth.login_success --locale=fr --params='{"name":"Jean"}'
# Output: "Bon retour, Jean !"

# Generate missing translation report
php bin/console i18n:missing --locale=es
# Shows which tokens are missing translations`} />

            {/* CI/CD Integration */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                CI/CD Integration
            </Typography>

            <CodeBlock language="yaml" title=".github/workflows/i18n-check.yml" code={`name: I18n Validation

on: [push, pull_request]

jobs:
  i18n-check:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        
    - name: Install dependencies
      run: composer install
      
    - name: Scan for new translations
      run: php bin/console i18n:scan --ci
      
    - name: Validate translation syntax
      run: php bin/console i18n:lint --strict
      
    - name: Check for missing translations
      run: php bin/console i18n:missing --fail-on-missing`} />

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>I18n Best Practices:</strong>
                <br />• Use hierarchical token names (auth.login_success, user.profile.updated)
                <br />• Keep translations in the code close to where they're used
                <br />• Use AI providers for initial translations, then refine manually
                <br />• Test translations with actual data to catch formatting issues
                <br />• Set up CI/CD checks to prevent missing translations in production
                <br />• Use context when the same word has different meanings
                <br />• Leverage ICU message format for complex pluralization
            </Alert>
        </Box>
    );
}
