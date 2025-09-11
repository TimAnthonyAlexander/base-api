<?php

namespace BaseApi\Database\Migrations;

class DatabaseSchema
{
    public function __construct(
        /** @var array<string, TableDef> */
        public array $tables = []
    ) {}

    public function toArray(): array
    {
        return [
            'tables' => array_map(fn(TableDef $table) => $table->toArray(), $this->tables),
        ];
    }

    public static function fromArray(array $data): self
    {
        $schema = new self();
        
        foreach ($data['tables'] ?? [] as $tableData) {
            $table = TableDef::fromArray($tableData);
            $schema->tables[$table->name] = $table;
        }
        
        return $schema;
    }
}
