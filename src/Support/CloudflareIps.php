<?php

namespace BaseApi\Support;

/**
 * Cloudflare's published edge ranges, the addresses a proxied request reaches
 * the origin from. Source: https://www.cloudflare.com/ips-v4 and /ips-v6,
 * checked 2026-10-08 (API etag 38f79d050aa027e3be3865e495dcc9bc).
 */
class CloudflareIps
{
    public const array RANGES = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    public static function contains(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return false;
        }

        foreach (self::RANGES as $range) {
            [$network, $bits] = explode('/', $range);
            $net = inet_pton($network);
            if (strlen($net) !== strlen($packed)) {
                continue;
            }

            if (self::matches($packed, $net, (int) $bits)) {
                return true;
            }
        }

        return false;
    }

    private static function matches(string $ip, string $net, int $bits): bool
    {
        $bytes = intdiv($bits, 8);
        if (strncmp($ip, $net, $bytes) !== 0) {
            return false;
        }

        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $rest)) & 0xFF;

        return (ord($ip[$bytes]) & $mask) === (ord($net[$bytes]) & $mask);
    }
}
