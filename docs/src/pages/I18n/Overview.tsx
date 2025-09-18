
import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function Internationalization() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Internationalization (I18n)
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        BaseAPI's comprehensive internationalization system with automated translation tools
      </Typography>

      <Typography paragraph>
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
        Workflow Example
      </Typography>

      <CodeBlock language="bash" code={`# 1. Add translations to your code
echo I18n::t('user.profile.title');

# 2. Scan codebase to find new tokens
php bin/console i18n:scan

# 3. Add a new language
php bin/console i18n:add-lang fr

# 4. Fill missing translations with AI
php bin/console i18n:fill --provider=openai --locale=fr

# 5. Validate translations
php bin/console i18n:lint`} />

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>I18n Best Practices:</strong>
        <br />• Use descriptive, hierarchical token names
        <br />• Leverage AI translation filling for initial translations
        <br />• Always validate translations with i18n:lint
        <br />• Scan regularly during development to catch new tokens
      </Alert>
    </Box>
  );
}