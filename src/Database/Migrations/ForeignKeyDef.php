<?php

namespace BaseApi\Database\Migrations;

class ForeignKeyDef
{
    public function __construct(
        public string $name,
        public string $column,
        public string $ref_table,
        public string $ref_column,
        public string $on_delete = 'RESTRICT',
        public string $on_update = 'CASCADE'
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'column' => $this->column,
            'ref_table' => $this->ref_table,
            'ref_column' => $this->ref_column,
            'on_delete' => $this->on_delete,
            'on_update' => $this->on_update,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['column'],
            $data['ref_table'],
            $data['ref_column'],
            $data['on_delete'] ?? 'RESTRICT',
            $data['on_update'] ?? 'CASCADE'
        );
    }
}
