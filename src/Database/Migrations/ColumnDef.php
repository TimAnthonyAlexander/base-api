<?php

namespace BaseApi\Database\Migrations;

class ColumnDef
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable = false,
        public ?string $default = null,
        public bool $is_pk = false,
        public ?string $generated = null,
        public bool $stored = false
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'nullable' => $this->nullable,
            'default' => $this->default,
            'is_pk' => $this->is_pk,
            'generated' => $this->generated,
            'stored' => $this->stored,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['type'],
            $data['nullable'] ?? false,
            $data['default'] ?? null,
            $data['is_pk'] ?? false,
            $data['generated'] ?? null,
            $data['stored'] ?? false
        );
    }
}
