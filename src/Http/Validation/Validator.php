<?php

namespace BaseApi\Http\Validation;

use BaseApi\Http\UploadedFile;

class Validator
{
    private static array $customRules = [];
    
    public function validate(object $controller, array $rules, array $messages = []): void
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
            
            $error = $this->validateField($field, $value, $fieldRules, $controller, $messages);
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

    private function validateField(string $field, mixed $value, array $rules, object $controller, array $messages): ?string
    {
        // Validate in order: required → type-ish rules → value rules
        
        // Required check first
        if (isset($rules['required'])) {
            if ($this->isEmpty($value)) {
                return $this->getCustomMessage($field, 'required', $messages, "The {$field} field is required.");
            }
        }

        // If value is empty and not required, skip other validations
        if ($this->isEmpty($value) && !isset($rules['required'])) {
            return null;
        }

        // Confirmed rule - checks for matching confirmation field
        if (isset($rules['confirmed'])) {
            $confirmationField = is_string($rules['confirmed']) ? $rules['confirmed'] : $field . '_confirmation';
            $confirmationValue = $this->getFieldValue($controller, $confirmationField);
            
            if ($value !== $confirmationValue) {
                return $this->getCustomMessage($field, 'confirmed', $messages, "The {$field} confirmation does not match.");
            }
        }

        // Type-ish rules
        if (isset($rules['boolean']) && !is_bool($value)) {
            return $this->getCustomMessage($field, 'boolean', $messages, "The {$field} field must be a boolean.");
        }

        if (isset($rules['integer']) && !is_int($value)) {
            return $this->getCustomMessage($field, 'integer', $messages, "The {$field} field must be an integer.");
        }

        if (isset($rules['numeric']) && !is_numeric($value)) {
            return $this->getCustomMessage($field, 'numeric', $messages, "The {$field} field must be numeric.");
        }

        if (isset($rules['array']) && !is_array($value)) {
            return $this->getCustomMessage($field, 'array', $messages, "The {$field} field must be an array.");
        }

        if (isset($rules['file'])) {
            if (!($value instanceof UploadedFile)) {
                return $this->getCustomMessage($field, 'file', $messages, "The {$field} field must be a file.");
            }
            if (!$value->isValid()) {
                return $this->getCustomMessage($field, 'file', $messages, "The {$field} file upload failed.");
            }
        }

        // Enhanced image validation
        if (isset($rules['image'])) {
            if (!($value instanceof UploadedFile)) {
                return $this->getCustomMessage($field, 'image', $messages, "The {$field} field must be an image file.");
            }
            if (!$value->isValid()) {
                return $this->getCustomMessage($field, 'image', $messages, "The {$field} image upload failed.");
            }
            
            $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
            if (!in_array($value->getMimeType(), $imageMimes)) {
                return $this->getCustomMessage($field, 'image', $messages, "The {$field} field must be an image file.");
            }
        }

        if (isset($rules['email']) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->getCustomMessage($field, 'email', $messages, "The {$field} field must be a valid email address.");
        }

        if (isset($rules['uuid']) && !$this->isValidUuid($value)) {
            return $this->getCustomMessage($field, 'uuid', $messages, "The {$field} field must be a valid UUID.");
        }

        // Image dimension rules
        if ($value instanceof UploadedFile && $this->isImage($value)) {
            $imageInfo = getimagesize($value->tmpName);
            if ($imageInfo) {
                [$width, $height] = $imageInfo;
                
                if (isset($rules['max_width'])) {
                    $maxWidth = (int) $rules['max_width'];
                    if ($width > $maxWidth) {
                        return $this->getCustomMessage($field, 'max_width', $messages, "The {$field} image width must not exceed {$maxWidth} pixels.");
                    }
                }
                
                if (isset($rules['max_height'])) {
                    $maxHeight = (int) $rules['max_height'];
                    if ($height > $maxHeight) {
                        return $this->getCustomMessage($field, 'max_height', $messages, "The {$field} image height must not exceed {$maxHeight} pixels.");
                    }
                }
                
                if (isset($rules['dimensions'])) {
                    $error = $this->validateDimensions($field, $width, $height, $rules['dimensions'], $messages);
                    if ($error) {
                        return $error;
                    }
                }
            }
        }

        // Value rules
        if (isset($rules['min'])) {
            $min = (int) $rules['min'];
            if (is_string($value) && strlen($value) < $min) {
                return $this->getCustomMessage($field, 'min', $messages, "The {$field} field must be at least {$min} characters.", ['min' => $min]);
            }
            if (is_numeric($value) && $value < $min) {
                return $this->getCustomMessage($field, 'min', $messages, "The {$field} field must be at least {$min}.", ['min' => $min]);
            }
            if (is_array($value) && count($value) < $min) {
                return $this->getCustomMessage($field, 'min', $messages, "The {$field} field must have at least {$min} items.", ['min' => $min]);
            }
        }

        if (isset($rules['max'])) {
            $max = (int) $rules['max'];
            if (is_string($value) && strlen($value) > $max) {
                return $this->getCustomMessage($field, 'max', $messages, "The {$field} field must not exceed {$max} characters.", ['max' => $max]);
            }
            if (is_numeric($value) && $value > $max) {
                return $this->getCustomMessage($field, 'max', $messages, "The {$field} field must not exceed {$max}.", ['max' => $max]);
            }
            if (is_array($value) && count($value) > $max) {
                return $this->getCustomMessage($field, 'max', $messages, "The {$field} field must not have more than {$max} items.", ['max' => $max]);
            }
        }

        if (isset($rules['in'])) {
            $allowed = explode(',', $rules['in']);
            if (!in_array($value, $allowed, true)) {
                return $this->getCustomMessage($field, 'in', $messages, "The {$field} field must be one of: " . implode(', ', $allowed) . ".");
            }
        }

        if (isset($rules['mimes']) && $value instanceof UploadedFile) {
            $allowedMimes = explode(',', $rules['mimes']);
            $extension = $value->getExtension();
            $mimeType = $value->getMimeType();
            
            $isValidExtension = in_array($extension, $allowedMimes);
            $isValidMime = false;
            
            // Enhanced MIME type mapping
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'pdf' => 'application/pdf',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'bmp' => 'image/bmp',
                'svg' => 'image/svg+xml'
            ];
            
            foreach ($allowedMimes as $allowed) {
                if (isset($mimeMap[$allowed]) && $mimeMap[$allowed] === $mimeType) {
                    $isValidMime = true;
                    break;
                }
            }
            
            if (!$isValidExtension && !$isValidMime) {
                return $this->getCustomMessage($field, 'mimes', $messages, "The {$field} field must be a file of type: " . implode(', ', $allowedMimes) . ".");
            }
        }

        if (isset($rules['size']) && $value instanceof UploadedFile) {
            $maxSize = (float) $rules['size']; // MB
            if ($value->getSizeInMB() > $maxSize) {
                return $this->getCustomMessage($field, 'size', $messages, "The {$field} field must not exceed {$maxSize}MB.", ['size' => $maxSize]);
            }
        }

        // Check custom rules
        foreach ($rules as $ruleName => $parameter) {
            if (isset(self::$customRules[$ruleName])) {
                $customRule = self::$customRules[$ruleName];
                $isValid = is_callable($customRule) 
                    ? call_user_func($customRule, $value, $parameter, $controller)
                    : $customRule->validate($value, $parameter, $controller);
                
                if (!$isValid) {
                    return $this->getCustomMessage($field, $ruleName, $messages, "The {$field} field failed {$ruleName} validation.");
                }
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

    private function getCustomMessage(string $field, string $rule, array $messages, string $default, array $placeholders = []): string
    {
        // Check for field-specific message: email.required
        $fieldRuleKey = "{$field}.{$rule}";
        if (isset($messages[$fieldRuleKey])) {
            return $this->interpolateMessage($messages[$fieldRuleKey], $field, $placeholders);
        }

        // Check for rule-specific message: required
        if (isset($messages[$rule])) {
            return $this->interpolateMessage($messages[$rule], $field, $placeholders);
        }

        // Return default message
        return $this->interpolateMessage($default, $field, $placeholders);
    }

    private function interpolateMessage(string $message, string $field, array $placeholders = []): string
    {
        $message = str_replace(':field', $field, $message);
        $message = str_replace(':attribute', $field, $message);
        
        foreach ($placeholders as $key => $value) {
            $message = str_replace(":{$key}", (string) $value, $message);
        }
        
        return $message;
    }

    private function getFieldValue(object $controller, string $field): mixed
    {
        $reflection = new \ReflectionClass($controller);
        
        if (!$reflection->hasProperty($field)) {
            return null;
        }

        $property = $reflection->getProperty($field);
        if (!$property->isPublic()) {
            return null;
        }

        return $property->getValue($controller);
    }

    private function isImage(UploadedFile $file): bool
    {
        $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        return in_array($file->getMimeType(), $imageMimes);
    }

    private function validateDimensions(string $field, int $width, int $height, string $dimensionsRule, array $messages): ?string
    {
        $constraints = [];
        $parts = explode(',', $dimensionsRule);
        
        foreach ($parts as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', trim($part), 2);
                $constraints[trim($key)] = (int) trim($value);
            }
        }
        
        if (isset($constraints['min_width']) && $width < $constraints['min_width']) {
            return $this->getCustomMessage($field, 'min_width', $messages, "The {$field} image width must be at least {$constraints['min_width']} pixels.");
        }
        
        if (isset($constraints['max_width']) && $width > $constraints['max_width']) {
            return $this->getCustomMessage($field, 'max_width', $messages, "The {$field} image width must not exceed {$constraints['max_width']} pixels.");
        }
        
        if (isset($constraints['min_height']) && $height < $constraints['min_height']) {
            return $this->getCustomMessage($field, 'min_height', $messages, "The {$field} image height must be at least {$constraints['min_height']} pixels.");
        }
        
        if (isset($constraints['max_height']) && $height > $constraints['max_height']) {
            return $this->getCustomMessage($field, 'max_height', $messages, "The {$field} image height must not exceed {$constraints['max_height']} pixels.");
        }
        
        return null;
    }

    /**
     * Register a custom validation rule
     */
    public static function extend(string $name, callable|object $rule): void
    {
        self::$customRules[$name] = $rule;
    }

    /**
     * Validate using attribute-based rules (PHP 8+)
     */
    public function validateWithAttributes(object $controller, array $messages = []): void
    {
        $errors = [];
        $reflection = new \ReflectionClass($controller);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $field = $property->getName();
            $value = $property->getValue($controller);
            $attributes = $property->getAttributes();
            
            $rules = [];
            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getName();
                $ruleMapping = $this->getAttributeRuleMapping();
                
                if (isset($ruleMapping[$attributeName])) {
                    $ruleName = $ruleMapping[$attributeName];
                    $args = $attribute->getArguments();
                    $rules[$ruleName] = !empty($args) ? $args[0] : true;
                }
            }
            
            if (!empty($rules)) {
                $error = $this->validateField($field, $value, $rules, $controller, $messages);
                if ($error) {
                    $errors[$field] = $error;
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function getAttributeRuleMapping(): array
    {
        return [
            'BaseApi\\Http\\Validation\\Attributes\\Required' => 'required',
            'BaseApi\\Http\\Validation\\Attributes\\Email' => 'email',
            'BaseApi\\Http\\Validation\\Attributes\\Min' => 'min',
            'BaseApi\\Http\\Validation\\Attributes\\Max' => 'max',
            'BaseApi\\Http\\Validation\\Attributes\\Confirmed' => 'confirmed',
            'BaseApi\\Http\\Validation\\Attributes\\Numeric' => 'numeric',
            'BaseApi\\Http\\Validation\\Attributes\\Integer' => 'integer',
            'BaseApi\\Http\\Validation\\Attributes\\Boolean' => 'boolean',
            'BaseApi\\Http\\Validation\\Attributes\\ArrayAttribute' => 'array',
            'BaseApi\\Http\\Validation\\Attributes\\File' => 'file',
            'BaseApi\\Http\\Validation\\Attributes\\Image' => 'image',
            'BaseApi\\Http\\Validation\\Attributes\\Uuid' => 'uuid',
            'BaseApi\\Http\\Validation\\Attributes\\In' => 'in',
            'BaseApi\\Http\\Validation\\Attributes\\Mimes' => 'mimes',
            'BaseApi\\Http\\Validation\\Attributes\\Size' => 'size',
        ];
    }
}
