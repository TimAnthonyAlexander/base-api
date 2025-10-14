<?php

namespace BaseApi\Database;

class PaginatedResult
{
    public ?int $remaining;

    public function __construct(
        public array $data,
        public int $page,
        public int $perPage,
        public ?int $total = null
    ) {
        $this->remaining = $this->total !== null ? max(0, $this->total - ($this->page * $this->perPage)) : null;
    }

    /**
     * Returns headers that controllers can set for pagination metadata
     */
    public function headers(): array
    {
        $headers = [
            'X-Page' => (string) $this->page,
            'X-Per-Page' => (string) $this->perPage,
        ];

        if ($this->total !== null) {
            $headers['X-Total'] = (string) $this->total;
        }

        return $headers;
    }
}
