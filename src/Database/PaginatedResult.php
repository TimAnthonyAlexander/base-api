<?php

namespace BaseApi\Database;

class PaginatedResult
{
    public array $data;
    public int $page;
    public int $perPage;
    public ?int $total;
    public ?int $remaining;

    public function __construct(
        array $data,
        int $page,
        int $perPage,
        ?int $total = null
    ) {
        $this->data = $data;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->total = $total;
        $this->remaining = $total !== null ? max(0, $total - ($page * $perPage)) : null;
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
