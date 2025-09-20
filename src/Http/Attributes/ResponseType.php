<?php

namespace BaseApi\Http\Attributes;

use Attribute;

/**
 * Describes the successful response data shape for a handler method.
 * 
 * Usage:
 * #[ResponseType(UserDto::class)]                              // 200 { data: UserDto }
 * #[ResponseType('UserDto[]', status: 200, when: 'list')]     // 200 { data: UserDto[] }
 * #[ResponseType(['message' => 'string'], status: 201)]       // 201 { data: { message: string } }
 * #[ResponseType]                                              // Auto-infer from return statements
 * #[ResponseType(status: 201)]                                 // Auto-infer with custom status
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ResponseType
{
    public function __construct(
        public array|string|null $shape = null,
        public ?int $status = 200,
        public ?string $when = null
    ) {
    }

    /**
     * Parse shape into a normalized format for code generation
     */
    public function getShapeInfo(): array
    {
        if ($this->shape === null) {
            return ['type' => 'null'];
        }

        if (is_string($this->shape)) {
            // Handle array types like 'UserDto[]'
            if (str_ends_with($this->shape, '[]')) {
                return [
                    'type' => 'array',
                    'items' => substr($this->shape, 0, -2)
                ];
            }

            // Single class/type reference
            return [
                'type' => 'class',
                'class' => $this->shape
            ];
        }

        // Must be array since we've checked null and string above
        // Inline object shape like ['message' => 'string', 'count' => 'int']
        return [
            'type' => 'object',
            'properties' => $this->shape
        ];
    }

    /**
     * Check if this shape represents a class/DTO reference
     */
    public function isClassReference(): bool
    {
        $info = $this->getShapeInfo();
        return $info['type'] === 'class' || ($info['type'] === 'array' && class_exists($info['items']));
    }

    /**
     * Get all class references in this shape
     */
    public function getClassReferences(): array
    {
        $info = $this->getShapeInfo();
        
        if ($info['type'] === 'class') {
            return [$info['class']];
        }
        
        if ($info['type'] === 'array' && class_exists($info['items'])) {
            return [$info['items']];
        }
        
        return [];
    }

    /**
     * Auto-infer the response type from a method's implementation
     */
    public function inferFromMethod(\ReflectionMethod $method): self
    {
        if ($this->shape !== null) {
            return $this; // Already has a shape, don't infer
        }

        // Try to infer from return statements in the method
        $inferredShape = $this->analyzeMethodReturns($method);
        
        if ($inferredShape !== null) {
            $this->shape = $inferredShape;
        }

        return $this;
    }

    /**
     * Analyze method's return statements to infer response type
     */
    private function analyzeMethodReturns(\ReflectionMethod $method): ?string
    {
        $filename = $method->getDeclaringClass()->getFileName();
        if (!$filename) {
            return null;
        }

        $content = file_get_contents($filename);
        $lines = explode("\n", $content);
        
        $startLine = $method->getStartLine() - 1;
        $endLine = $method->getEndLine() - 1;
        
        $methodBody = implode("\n", array_slice($lines, $startLine, $endLine - $startLine + 1));

        // Look for JsonResponse::ok() calls with model serialization
        if (preg_match_all('/JsonResponse::ok\(\s*\$(\w+)->jsonSerialize\(\s*\)\s*\)/', $methodBody, $matches)) {
            $variableName = $matches[1][0]; // Get first match
            
            // Try to find where this variable is instantiated or assigned
            if (preg_match('/\$' . $variableName . '\s*=\s*new\s+(\w+)\s*\(/', $methodBody, $typeMatches)) {
                $className = $typeMatches[1];
                
                // Add namespace if it's not fully qualified
                if (!class_exists($className)) {
                    $namespace = $method->getDeclaringClass()->getNamespaceName();
                    $fullClassName = $namespace . '\\Models\\' . $className;
                    if (class_exists($fullClassName)) {
                        $className = $fullClassName;
                    }
                }
                
                return $className;
            }

            // Try to find typed properties that match the variable name
            $class = $method->getDeclaringClass();
            if ($class->hasProperty($variableName)) {
                $property = $class->getProperty($variableName);
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType) {
                    return $type->getName();
                }
            }
        }

        // Look for JsonResponse::ok() with direct class instantiation
        if (preg_match('/JsonResponse::ok\(\s*\(new\s+(\w+)\s*\([^)]*\)\)->jsonSerialize\(\s*\)\s*\)/', $methodBody, $matches)) {
            $className = $matches[1];
            
            // Add namespace if needed
            if (!class_exists($className)) {
                $namespace = $method->getDeclaringClass()->getNamespaceName();
                $fullClassName = $namespace . '\\Models\\' . $className;
                if (class_exists($fullClassName)) {
                    $className = $fullClassName;
                }
            }
            
            return $className;
        }

        return null;
    }

    /**
     * Create a ResponseType with auto-inference for a specific method
     */
    public static function infer(\ReflectionMethod $method, ?int $status = 200, ?string $when = null): self
    {
        $instance = new self(null, $status, $when);
        return $instance->inferFromMethod($method);
    }
}
