<?php

namespace BaseApi\Http\Binding;

use ReflectionClass;
use ReflectionProperty;
use BaseApi\Http\Request;
use BaseApi\Http\UploadedFile;

class ControllerBinder
{
    public function bind(object $controller, Request $req, array $routeParams): void
    {
        // First, set the request object if the controller has a request property
        if (property_exists($controller, 'request')) {
            $controller->request = $req;
        }

        $reflection = new ReflectionClass($controller);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            
            // Skip the request property as it's already set above
            if ($propertyName === 'request') {
                continue;
            }
            
            // Get value with precedence: route params → query → body → files
            $value = $this->getValueWithPrecedence($propertyName, $routeParams, $req);
            
            // Only set the property if a value was provided in the request
            // Skip if no value provided AND (property has default OR property is not nullable)
            if ($value === null) {
                // If property has a default value, keep it
                if ($property->hasDefaultValue()) {
                    continue;
                }
                
                // If property is not nullable and has no default, skip setting it
                // (leave it uninitialized - validation will catch it if required)
                $type = $property->getType();
                if ($type && !$type->allowsNull()) {
                    continue;
                }
            }

            // Coerce the value to the property type
            $type = $property->getType();
            $coercedValue = TypeCoercion::coerce($value, $type);
            
            // Set the property value
            $property->setValue($controller, $coercedValue);
        }
    }

    private function getValueWithPrecedence(string $propertyName, array $routeParams, Request $req): mixed
    {
        // 1. Route params (highest precedence)
        if (array_key_exists($propertyName, $routeParams)) {
            return $routeParams[$propertyName];
        }
        
        $snakeCaseName = $this->camelToSnakeCase($propertyName);
        if (array_key_exists($snakeCaseName, $routeParams)) {
            return $routeParams[$snakeCaseName];
        }

        // 2. Query parameters
        if (array_key_exists($propertyName, $req->query)) {
            return $req->query[$propertyName];
        }
        
        if (array_key_exists($snakeCaseName, $req->query)) {
            return $req->query[$snakeCaseName];
        }

        // 3. Body data
        if (array_key_exists($propertyName, $req->body)) {
            return $req->body[$propertyName];
        }
        
        if (array_key_exists($snakeCaseName, $req->body)) {
            return $req->body[$snakeCaseName];
        }

        // 4. Files (lowest precedence)
        if (array_key_exists($propertyName, $req->files)) {
            return $this->processFileValue($req->files[$propertyName]);
        }
        
        if (array_key_exists($snakeCaseName, $req->files)) {
            return $this->processFileValue($req->files[$snakeCaseName]);
        }

        return null;
    }

    private function processFileValue(mixed $fileData): mixed
    {
        if (is_array($fileData)) {
            // Check if it's a single file
            if (isset($fileData['tmp_name'])) {
                return new UploadedFile($fileData);
            }
            
            // Check if it's an array of files (indexed array where each element has tmp_name)
            $isArrayOfFiles = false;
            $validFiles = [];
            
            foreach ($fileData as $key => $file) {
                if (is_int($key) && is_array($file) && isset($file['tmp_name'])) {
                    $isArrayOfFiles = true;
                    $validFiles[] = new UploadedFile($file);
                }
            }
            
            // If we found valid files in an indexed array, return them
            if ($isArrayOfFiles && $validFiles !== []) {
                return $validFiles;
            }
            
            // If it's neither a single file nor an array of files, return unchanged
            return $fileData;
        }

        return $fileData;
    }

    private function camelToSnakeCase(string $input): string
    {
        return strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
    }
}
