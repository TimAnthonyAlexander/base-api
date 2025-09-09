<?php

namespace BaseApi\Console;

interface Command
{
    public function name(): string;
    
    public function description(): string;
    
    public function execute(array $args): int;
}
