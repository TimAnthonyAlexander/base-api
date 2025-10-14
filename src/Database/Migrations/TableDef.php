<?php

namespace BaseApi\Database\Migrations;

class TableDef
{
    public function __construct(
        public string $name,
        /** @var array<string, ColumnDef> */
        public array $columns = [],
        /** @var array<string, IndexDef> */
        public array $indexes = [],
        /** @var array<string, ForeignKeyDef> */
        public array $fks = []
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'columns' => array_map(fn(ColumnDef $col): array => $col->toArray(), $this->columns),
            'indexes' => array_map(fn(IndexDef $idx): array => $idx->toArray(), $this->indexes),
            'fks' => array_map(fn(ForeignKeyDef $fk): array => $fk->toArray(), $this->fks),
        ];
    }

    public static function fromArray(array $data): self
    {
        $table = new self($data['name']);
        
        foreach ($data['columns'] ?? [] as $colData) {
            $col = ColumnDef::fromArray($colData);
            $table->columns[$col->name] = $col;
        }
        
        foreach ($data['indexes'] ?? [] as $idxData) {
            $idx = IndexDef::fromArray($idxData);
            $table->indexes[$idx->name] = $idx;
        }
        
        foreach ($data['fks'] ?? [] as $fkData) {
            $fk = ForeignKeyDef::fromArray($fkData);
            $table->fks[$fk->name] = $fk;
        }
        
        return $table;
    }
}
