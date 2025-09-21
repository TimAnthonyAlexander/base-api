<?php

namespace BaseApi\Database\Migrations;

class MigrationPlan
{
    public function __construct(
        /** @var array<array{op: string, table?: string, column?: array, fk?: array, destructive: bool}> */
        public array $operations = []
    ) {}

    public function addOperation(string $op, array $data = []): void
    {
        $this->operations[] = array_merge(['op' => $op], $data);
    }

    public function toArray(): array
    {
        return [
            'generated_at' => date('c'),
            'plan' => $this->operations,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['plan'] ?? []);
    }

    public function isEmpty(): bool
    {
        return $this->operations === [];
    }

    public function getDestructiveCount(): int
    {
        return count(array_filter($this->operations, fn($op): bool => $op['destructive']));
    }

    public function getNonDestructiveOperations(): array
    {
        return array_filter($this->operations, fn($op): bool => !$op['destructive']);
    }
}
