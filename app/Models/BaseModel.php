<?php

namespace BaseApi\Models;

use BaseApi\App;
use BaseApi\Support\Uuid;
use BaseApi\Database\QueryBuilder;

abstract class BaseModel implements \JsonSerializable
{
    public string $id = '';
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected static ?string $table = null;

    public static function table(): string
    {
        if (static::$table !== null) {
            return static::$table;
        }

        // Convert ClassName to table_name
        $className = (new \ReflectionClass(static::class))->getShortName();
        
        // Convert PascalCase to snake_case and pluralize
        $tableName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));
        
        // Simple pluralization
        if (str_ends_with($tableName, 'y')) {
            $tableName = substr($tableName, 0, -1) . 'ies';
        } elseif (str_ends_with($tableName, 's') || str_ends_with($tableName, 'sh') || str_ends_with($tableName, 'ch')) {
            $tableName .= 'es';
        } else {
            $tableName .= 's';
        }
        
        return $tableName;
    }

    public static function find(string $id): ?static
    {
        $row = App::db()->qb()
            ->table(static::table())
            ->where('id', '=', $id)
            ->first();

        return $row ? static::fromRow($row) : null;
    }

    public static function where(string $column, string $operator, mixed $value): QueryBuilder
    {
        return App::db()->qb()
            ->table(static::table())
            ->where($column, $operator, $value);
    }

    public static function firstWhere(string $column, string $operator, mixed $value): ?static
    {
        $row = static::where($column, $operator, $value)->first();
        return $row ? static::fromRow($row) : null;
    }

    public static function all(int $limit = 1000, int $offset = 0): array
    {
        $rows = App::db()->qb()
            ->table(static::table())
            ->limit($limit)
            ->offset($offset)
            ->get();

        return array_map([static::class, 'fromRow'], $rows);
    }

    public function save(): bool
    {
        if (empty($this->id)) {
            return $this->insert();
        } else {
            return $this->update();
        }
    }

    public function delete(): bool
    {
        if (empty($this->id)) {
            return false;
        }

        $affected = App::db()->qb()
            ->table(static::table())
            ->where('id', '=', $this->id)
            ->delete();

        return $affected > 0;
    }

    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $data = [];
        foreach ($properties as $property) {
            $data[$property->getName()] = $property->getValue($this);
        }
        
        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    protected static function fromRow(array $row): static
    {
        $instance = new static();
        
        $reflection = new \ReflectionClass($instance);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        foreach ($properties as $property) {
            $name = $property->getName();
            if (array_key_exists($name, $row)) {
                $value = $row[$name];
                
                // Simple type casting based on property type
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $value = match ($type->getName()) {
                        'int' => (int) $value,
                        'float' => (float) $value,
                        'bool' => (bool) $value,
                        'string' => (string) $value,
                        default => $value,
                    };
                }
                
                $property->setValue($instance, $value);
            }
        }
        
        return $instance;
    }

    private function insert(): bool
    {
        // Generate UUID if not set
        if (empty($this->id)) {
            $this->id = Uuid::v7();
        }

        $data = $this->getInsertData();
        
        App::db()->qb()
            ->table(static::table())
            ->insert($data);

        return true;
    }

    private function update(): bool
    {
        $data = $this->getUpdateData();
        
        if (empty($data)) {
            return true; // Nothing to update
        }

        $affected = App::db()->qb()
            ->table(static::table())
            ->where('id', '=', $this->id)
            ->update($data);

        return $affected > 0;
    }

    private function getInsertData(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $property->getValue($this);
            
            // Skip timestamps for insert (let DB handle them)
            if (in_array($name, ['created_at', 'updated_at']) && $value === null) {
                continue;
            }
            
            if ($value !== null) {
                $data[$name] = $value;
            }
        }
        
        return $data;
    }

    private function getUpdateData(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $property->getValue($this);
            
            // Skip id and created_at for updates
            if (in_array($name, ['id', 'created_at'])) {
                continue;
            }
            
            if ($value !== null) {
                $data[$name] = $value;
            }
        }
        
        return $data;
    }
}
