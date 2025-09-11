<?php

namespace BaseApi\Http\Binding;

use BaseApi\Http\UploadedFile;

class TypeCoercion
{
    public static function coerce(mixed $value, \ReflectionType|null $target): mixed
    {
        if ($target === null) {
            return $value;
        }

        // Handle union types (get the first non-null type)
        if ($target instanceof \ReflectionUnionType) {
            foreach ($target->getTypes() as $type) {
                if ($type->getName() !== 'null') {
                    return self::coerceToSingleType($value, $type);
                }
            }
            return $value;
        }

        return self::coerceToSingleType($value, $target);
    }

    private static function coerceToSingleType(mixed $value, \ReflectionType $target): mixed
    {
        if (!$target instanceof \ReflectionNamedType) {
            return $value; // Return as-is for complex types
        }
        
        $typeName = $target->getName();

        return match ($typeName) {
            'bool' => self::boolStringToBool($value),
            'int' => self::numericToInt($value),
            'float' => self::numericToFloat($value),
            'string' => self::toString($value),
            'array' => self::toArray($value),
            UploadedFile::class => self::toUploadedFile($value),
            default => $value
        };
    }

    public static function boolStringToBool(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            return $result !== null ? $result : $value;
        }

        return $value;
    }

    public static function numericToInt(mixed $value): mixed
    {
        if (is_int($value)) {
            return $value;
        }

        // Only convert if it's actually numeric
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        // Return original value so validator can catch invalid input
        return $value;
    }

    public static function numericToFloat(mixed $value): mixed
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }

    public static function toString(mixed $value): mixed
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        // Arrays not auto-stringified
        return $value;
    }

    public static function toArray(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        // Singletons not auto-wrapped (keep KISS)
        return $value;
    }

    public static function toUploadedFile(mixed $value): mixed
    {
        // Only from files map; body/query values never coerced to file
        if (is_array($value) && isset($value['tmp_name'])) {
            return new UploadedFile($value);
        }

        return $value;
    }
}
