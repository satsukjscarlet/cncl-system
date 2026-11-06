<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustCloudflareClientIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CloudflareClientIpTest extends TestCase
{
    protected function tearDown(): void
    {
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
        parent::tearDown();
    }

    public function test_global_pipeline_uses_cloudflare_visitor_ip_without_changing_host_or_scheme(): void
    {
        Route::get('/_test/client-ip', fn (Request $request) => [
            'ip' => $request->ip(), 'host' => $request->getHost(), 'secure' => $request->isSecure(),
        ]);
        $this->withServerVariables(['REMOTE_ADDR' => '162.158.179.193'])
            ->withHeaders(['CF-Connecting-IP' => '113.160.1.2',
                'X-Forwarded-For' => '1.2.3.4', 'X-Forwarded-Host' => 'evil.example',
                'X-Forwarded-Proto' => 'https'])
            ->get('/_test/client-ip')->assertOk()
            ->assertJson(['ip' => '113.160.1.2', 'host' => 'localhost', 'secure' => false]);
    }

    public function test_direct_connections_cannot_spoof_client_ip(): void
    {
        foreach (['127.0.0.1', '192.168.1.10', '203.0.113.10'] as $peer) {
            $this->assertSame($peer, $this->resolve($peer, '8.8.8.8', '9.9.9.9'));
        }
    }

    public function test_ipv6_peers_and_visitors_are_supported(): void
    {
        $this->assertSame('2001:db8::123', $this->resolve('2400:cb00::1', '2001:db8::123'));
        $this->assertSame('113.160.1.2', $this->resolve('172.71.210.210', '113.160.1.2'));
    }

    public function test_invalid_or_missing_cf_header_does_not_fall_back_to_untrusted_xff(): void
    {
        foreach ([null, '', 'invalid', '8.8.8.8, 9.9.9.9', '8.8.8.8:443'] as $client) {
            $this->assertSame('162.158.179.193', $this->resolve('162.158.179.193', $client, '1.2.3.4'));
        }
    }

    public function test_disabled_feature_preserves_direct_peer_address(): void
    {
        config(['cloudflare.enabled' => false]);
        $this->assertSame('162.158.179.193', $this->resolve('162.158.179.193', '8.8.8.8'));
    }

    public function test_pseudo_ipv4_uses_original_ipv6(): void
    {
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '162.158.179.193']);
        $request->headers->set('CF-Connecting-IP', '240.1.2.3');
        $request->headers->set('CF-Connecting-IPv6', '2001:db8::5');
        $ip = app(TrustCloudflareClientIp::class)->handle($request, fn ($r) => $r->ip());
        $this->assertSame('2001:db8::5', $ip);
    }

    public function test_trust_does_not_leak_to_the_next_direct_request(): void
    {
        $this->assertSame('8.8.8.8', $this->resolve('162.158.179.193', '8.8.8.8'));
        $this->assertSame('203.0.113.10', $this->resolve('203.0.113.10', '8.8.8.8', '9.9.9.9'));
    }

    private function resolve(string $peer, ?string $client, ?string $xff = null): ?string
    {
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $peer]);
        if ($client !== null) $request->headers->set('CF-Connecting-IP', $client);
        if ($xff !== null) $request->headers->set('X-Forwarded-For', $xff);

        return app(TrustCloudflareClientIp::class)->handle($request, fn ($r) => $r->ip());
    }
}
