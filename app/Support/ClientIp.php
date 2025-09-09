<?php

namespace BaseApi\Support;

use BaseApi\Http\Request;

class ClientIp
{
    public static function from(Request $request, bool $trustProxy): string
    {
        if ($trustProxy && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($forwarded[0]);
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
