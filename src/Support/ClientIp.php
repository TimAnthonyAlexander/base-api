<?php

namespace BaseApi\Support;

use BaseApi\Http\Request;

class ClientIp
{
    public static function from(Request $request, bool $trustProxy): string
    {
        if ($trustProxy && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
            
            // Get the first (leftmost) IP and clean it up
            $ip = trim($forwarded[0]);
            
            // Basic validation - ensure it's not empty and looks like an IP
            if ($ip !== '' && $ip !== '0' && self::isValidIp($ip)) {
                return $ip;
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    private static function isValidIp(string $ip): bool
    {
        // Remove any port number (IPv6 format like [::1]:8080 or IPv4 like 127.0.0.1:8080)
        $ip = preg_replace('/:\d+$/', '', $ip);
        $ip = trim((string) $ip, '[]');
        
        // Validate IPv4 or IPv6
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
            || filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}
