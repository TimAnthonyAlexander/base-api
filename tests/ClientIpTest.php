<?php

namespace BaseApi\Tests;

use Override;
use PHPUnit\Framework\TestCase;
use BaseApi\Http\Request;
use BaseApi\Support\ClientIp;
use BaseApi\Support\CloudflareIps;

class ClientIpTest extends TestCase
{
    private array $server;

    #[Override]
    protected function setUp(): void
    {
        $this->server = $_SERVER;
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    #[Override]
    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    private function request(): Request
    {
        return new Request('GET', '/', [], [], [], null, [], [], [], 'test');
    }

    public function testCloudflareHeaderIsUsedWhenTheConnectionComesFromCloudflare(): void
    {
        $_SERVER['REMOTE_ADDR'] = '172.70.1.2';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.7';

        $this->assertSame('203.0.113.7', ClientIp::from($this->request(), false));
    }

    public function testCloudflareHeaderWorksOverIpv6(): void
    {
        $_SERVER['REMOTE_ADDR'] = '2a06:98c1::5';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '2001:db8::1';

        $this->assertSame('2001:db8::1', ClientIp::from($this->request(), false));
    }

    public function testCloudflareHeaderFromANonCloudflareAddressIsIgnored(): void
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.9';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.7';

        $this->assertSame('198.51.100.9', ClientIp::from($this->request(), false));
    }

    public function testInvalidCloudflareHeaderFallsBackToRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '172.70.1.2';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = 'not-an-ip';

        $this->assertSame('172.70.1.2', ClientIp::from($this->request(), false));
    }

    public function testCloudflareHeaderWinsOverForwardedFor(): void
    {
        $_SERVER['REMOTE_ADDR'] = '104.23.0.1';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1';

        $this->assertSame('203.0.113.7', ClientIp::from($this->request(), true));
    }

    public function testRemoteAddrWithoutHeaders(): void
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.9';

        $this->assertSame('198.51.100.9', ClientIp::from($this->request(), false));
    }

    public function testRangeBoundaries(): void
    {
        $this->assertTrue(CloudflareIps::contains('173.245.48.0'));
        $this->assertTrue(CloudflareIps::contains('173.245.63.255'));
        $this->assertFalse(CloudflareIps::contains('173.245.64.0'));
        $this->assertTrue(CloudflareIps::contains('162.159.255.255'));
        $this->assertFalse(CloudflareIps::contains('162.160.0.0'));
        $this->assertTrue(CloudflareIps::contains('2a06:98c7:ffff::1'));
        $this->assertFalse(CloudflareIps::contains('2a06:98c8::1'));
        $this->assertFalse(CloudflareIps::contains('152.53.127.163'));
        $this->assertFalse(CloudflareIps::contains(''));
        $this->assertFalse(CloudflareIps::contains('garbage'));
    }
}
