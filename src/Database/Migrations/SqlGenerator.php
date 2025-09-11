<?php

namespace BaseApi\Database\Migrations;

use BaseApi\App;

class SqlGenerator
{
    public function generate(MigrationPlan $plan): array
    {
        $connection = App::db()->getConnection();
        $driver = $connection->getDriver();
        
        return $driver->generateSql($plan);
    }
}