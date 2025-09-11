<?php

namespace BaseApi\Database\Migrations;

class IndexDef
{
    public function __construct(
        public string $name,
        public string $column,
        public string $type = 'index' // 'index' or 'unique'
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
}
