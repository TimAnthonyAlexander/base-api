<?php

namespace BaseApi\Http\Validation;

use BaseApi\Http\UploadedFile;

class Validator
{
    public function validate(object $controller, array $rules): void
    {
        $errors = [];
        $reflection = new \ReflectionClass($controller);

        foreach ($rules as $field => $ruleString) {
            if (!$reflection->hasProperty($field)) {
                continue; // Skip unknown properties
            }

            $property = $reflection->getProperty($field);
            if (!$property->isPublic()) {
                continue; // Skip non-public properties
            }

            $value = $property->getValue($controller);
            $fieldRules = $this->parseRules($ruleString);
            
            $error = $this->validateField($field, $value, $fieldRules);
            if ($error) {
                $errors[$field] = $error;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function parseRules(string $ruleString): array
    {
        $rules = [];
        $parts = explode('|', $ruleString);

        foreach ($parts as $part) {
            $part = trim($part);
            if (str_contains($part, ':')) {
                [$name, $parameter] = explode(':', $part, 2);
                $rules[trim($name)] = trim($parameter);
            } else {
                $rules[$part] = true;
            }
        }

        return $rules;
    }

    private function validateField(string $field, mixed $value, array $rules): ?string
    {
        // Validate in order: required → type-ish rules → value rules
        
        // Required check first
        if (isset($rules['required'])) {
            if ($this->isEmpty($value)) {
                return "The {$field} field is required.";
            }
        }

        // If value is empty and not required, skip other validations
        if ($this->isEmpty($value) && !isset($rules['required'])) {
            return null;
        }

        // Type-ish rules
        if (isset($rules['boolean']) && !is_bool($value)) {
            return "The {$field} field must be a boolean.";
        }

        if (isset($rules['integer']) && !is_int($value)) {
            return "The {$field} field must be an integer.";
        }

        if (isset($rules['numeric']) && !is_numeric($value)) {
            return "The {$field} field must be numeric.";
        }

        if (isset($rules['array']) && !is_array($value)) {
            return "The {$field} field must be an array.";
        }

        if (isset($rules['file'])) {
            if (!($value instanceof UploadedFile)) {
                return "The {$field} field must be a file.";
            }
            if (!$value->isValid()) {
                return "The {$field} file upload failed.";
            }
        }

        if (isset($rules['email']) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The {$field} field must be a valid email address.";
        }

        if (isset($rules['uuid']) && !$this->isValidUuid($value)) {
            return "The {$field} field must be a valid UUID.";
        }

        // Value rules
        if (isset($rules['min'])) {
            $min = (int) $rules['min'];
            if (is_string($value) && strlen($value) < $min) {
                return "The {$field} field must be at least {$min} characters.";
            }
            if (is_numeric($value) && $value < $min) {
                return "The {$field} field must be at least {$min}.";
            }
            if (is_array($value) && count($value) < $min) {
                return "The {$field} field must have at least {$min} items.";
            }
        }

        if (isset($rules['max'])) {
            $max = (int) $rules['max'];
            if (is_string($value) && strlen($value) > $max) {
                return "The {$field} field must not exceed {$max} characters.";
            }
            if (is_numeric($value) && $value > $max) {
                return "The {$field} field must not exceed {$max}.";
            }
            if (is_array($value) && count($value) > $max) {
                return "The {$field} field must not have more than {$max} items.";
            }
        }

        if (isset($rules['in'])) {
            $allowed = explode(',', $rules['in']);
            if (!in_array($value, $allowed, true)) {
                return "The {$field} field must be one of: " . implode(', ', $allowed) . ".";
            }
        }

        if (isset($rules['mimes']) && $value instanceof UploadedFile) {
            $allowedMimes = explode(',', $rules['mimes']);
            $extension = $value->getExtension();
            $mimeType = $value->getMimeType();
            
            $isValidExtension = in_array($extension, $allowedMimes);
            $isValidMime = false;
            
            // Simple MIME type mapping
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'pdf' => 'application/pdf',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            
            foreach ($allowedMimes as $allowed) {
                if (isset($mimeMap[$allowed]) && $mimeMap[$allowed] === $mimeType) {
                    $isValidMime = true;
                    break;
                }
            }
            
            if (!$isValidExtension && !$isValidMime) {
                return "The {$field} field must be a file of type: " . implode(', ', $allowedMimes) . ".";
            }
        }

        if (isset($rules['size']) && $value instanceof UploadedFile) {
            $maxSize = (float) $rules['size']; // MB
            if ($value->getSizeInMB() > $maxSize) {
                return "The {$field} field must not exceed {$maxSize}MB.";
            }
        }

        return null;
    }

    private function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if ($value === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        return false;
    }

    private function isValidUuid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Simple UUID format check (v4/v7 acceptable)
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }
}
