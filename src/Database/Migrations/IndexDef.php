<?php

namespace BaseApi\Database\Migrations;

class IndexDef
{
    public function __construct(
        public string $name,
        public string|array $column,
        public string $type = 'index' // 'index', 'unique', or 'fulltext'
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'column' => $this->column,
            'type' => $this->type,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['column'],
            $data['type'] ?? 'index'
        );
    }

    /**
     * Get columns as array (normalizes both string and array to array)
     * @return array<string>
     */
    public function getColumns(): array
    {
        return is_array($this->column) ? $this->column : [$this->column];
    }
}
