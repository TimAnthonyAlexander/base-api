<?php

namespace BaseApi\Tests\Integration;

use DateTime;
use BaseApi\Models\BaseModel;

class TestModel extends BaseModel
{
    public string $id;

    public string $name;

    public ?string $description = null;

    public int $count;

    public float $price;

    public bool $active = true;

    public DateTime $created_at;

    public ?DateTime $updated_at = null;
}
