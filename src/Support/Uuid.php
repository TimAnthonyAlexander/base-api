<?php

namespace BaseApi\Support;

class Uuid
{
    public static function v7(): string
    {
        // Get current timestamp in milliseconds
        $timestamp = (int)(microtime(true) * 1000);
        
        // Generate random bytes for the rest
        $randomBytes = random_bytes(10);
        
        // Convert timestamp to bytes (48 bits)
        $timestampHex = str_pad(dechex($timestamp), 12, '0', STR_PAD_LEFT);
        $timestampBytes = hex2bin($timestampHex);
        
        // Combine timestamp and random bytes
        $bytes = $timestampBytes . $randomBytes;
        
        // Set version (7) and variant bits
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x70); // Version 7
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); // Variant 10
        
        // Convert to hex string
        $hex = bin2hex($bytes);
        
        // Format as UUID string
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
