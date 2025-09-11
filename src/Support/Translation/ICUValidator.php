<?php

namespace BaseApi\Support\Translation;

class ICUValidator
{
    private array $errors = [];
    
    /**
     * Validate an ICU message format string
     */
    public function validate(string $message): bool
    {
        $this->errors = [];
        
        // Check brace balance
        if (!$this->checkBraceBalance($message)) {
            return false;
        }
        
        // Extract and validate placeholders
        $placeholders = $this->extractPlaceholders($message);
        foreach ($placeholders as $placeholder) {
            if (!$this->validatePlaceholder($placeholder)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get the last error message
     */
    public function getLastError(): ?string
    {
        return end($this->errors) ?: null;
    }
    
    /**
     * Check if braces are balanced
     */
    private function checkBraceBalance(string $message): bool
    {
        $openCount = substr_count($message, '{');
        $closeCount = substr_count($message, '}');
        
        if ($openCount !== $closeCount) {
            $this->errors[] = "Mismatched braces: {$openCount} opening, {$closeCount} closing";
            return false;
        }
        
        // Check for proper nesting
        $level = 0;
        $chars = str_split($message);
        
        foreach ($chars as $i => $char) {
            if ($char === '{') {
                $level++;
            } elseif ($char === '}') {
                $level--;
                if ($level < 0) {
                    $this->errors[] = "Unmatched closing brace at position {$i}";
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Extract placeholders from message
     */
    private function extractPlaceholders(string $message): array
    {
        $placeholders = [];
        $level = 0;
        $current = '';
        $chars = str_split($message);
        $inPlaceholder = false;
        
        foreach ($chars as $char) {
            if ($char === '{') {
                if ($level === 0) {
                    $inPlaceholder = true;
                    $current = '';
                }
                $level++;
                if ($level > 1) {
                    $current .= $char;
                }
            } elseif ($char === '}') {
                $level--;
                if ($level > 0) {
                    $current .= $char;
                } elseif ($level === 0 && $inPlaceholder) {
                    $placeholders[] = trim($current);
                    $inPlaceholder = false;
                    $current = '';
                }
            } elseif ($inPlaceholder) {
                $current .= $char;
            }
        }
        
        return $placeholders;
    }
    
    /**
     * Validate a single placeholder
     */
    private function validatePlaceholder(string $placeholder): bool
    {
        if (empty($placeholder)) {
            $this->errors[] = "Empty placeholder found";
            return false;
        }
        
        $parts = array_map('trim', explode(',', $placeholder));
        $variableName = $parts[0];
        
        // Validate variable name
        if (!$this->isValidVariableName($variableName)) {
            $this->errors[] = "Invalid variable name: '{$variableName}'";
            return false;
        }
        
        // If there's only one part, it's a simple variable
        if (count($parts) === 1) {
            return true;
        }
        
        // Validate format type
        if (count($parts) < 3) {
            $this->errors[] = "Invalid placeholder format: '{$placeholder}'";
            return false;
        }
        
        $formatType = strtolower($parts[1]);
        $formatStyle = $parts[2];
        
        switch ($formatType) {
            case 'number':
                return $this->validateNumberFormat($formatStyle, $placeholder);
            case 'date':
            case 'time':
                return $this->validateDateTimeFormat($formatStyle, $placeholder);
            case 'plural':
                return $this->validatePluralFormat($formatStyle, $placeholder);
            case 'select':
                return $this->validateSelectFormat($formatStyle, $placeholder);
            default:
                $this->errors[] = "Unknown format type: '{$formatType}' in '{$placeholder}'";
                return false;
        }
    }
    
    /**
     * Check if variable name is valid
     */
    private function isValidVariableName(string $name): bool
    {
        // Variable names should be alphanumeric with underscores
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name);
    }
    
    /**
     * Validate number format
     */
    private function validateNumberFormat(string $style, string $placeholder): bool
    {
        $validStyles = ['integer', 'currency', 'percent'];
        
        if (!in_array($style, $validStyles)) {
            $this->errors[] = "Invalid number format style: '{$style}' in '{$placeholder}'";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate date/time format
     */
    private function validateDateTimeFormat(string $style, string $placeholder): bool
    {
        $validStyles = ['short', 'medium', 'long', 'full'];
        
        if (!in_array($style, $validStyles)) {
            $this->errors[] = "Invalid date/time format style: '{$style}' in '{$placeholder}'";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate plural format
     */
    private function validatePluralFormat(string $options, string $placeholder): bool
    {
        // Parse plural options
        $cases = $this->parsePluralOptions($options);
        
        if (empty($cases)) {
            $this->errors[] = "No plural cases found in '{$placeholder}'";
            return false;
        }
        
        // Check for required 'other' case
        if (!isset($cases['other'])) {
            $this->errors[] = "Plural format missing required 'other' case in '{$placeholder}'";
            return false;
        }
        
        // Validate case names
        $validCases = ['zero', 'one', 'two', 'few', 'many', 'other'];
        foreach (array_keys($cases) as $case) {
            if (!in_array($case, $validCases)) {
                $this->errors[] = "Invalid plural case: '{$case}' in '{$placeholder}'";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate select format
     */
    private function validateSelectFormat(string $options, string $placeholder): bool
    {
        // Parse select options
        $cases = $this->parseSelectOptions($options);
        
        if (empty($cases)) {
            $this->errors[] = "No select cases found in '{$placeholder}'";
            return false;
        }
        
        // Check for required 'other' case
        if (!isset($cases['other'])) {
            $this->errors[] = "Select format missing required 'other' case in '{$placeholder}'";
            return false;
        }
        
        return true;
    }
    
    /**
     * Parse plural options from string
     */
    private function parsePluralOptions(string $options): array
    {
        $cases = [];
        
        // Simple parsing - look for pattern: case { content }
        if (preg_match_all('/(\w+)\s*\{([^}]*)\}/', $options, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $case = trim($match[1]);
                $content = trim($match[2]);
                $cases[$case] = $content;
            }
        }
        
        return $cases;
    }
    
    /**
     * Parse select options from string
     */
    private function parseSelectOptions(string $options): array
    {
        // Same as plural for now
        return $this->parsePluralOptions($options);
    }
}
