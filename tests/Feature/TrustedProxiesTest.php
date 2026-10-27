<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    public function test_uses_forwarded_https_scheme_from_a_trusted_proxy(): void
    {
        config()->set('trustedproxy.proxies', ['192.0.2.1']);
        Route::get('/_tests/proxy-root', static fn (Request $request): string => $request->root());

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])
            ->withHeaders([
                'X-Forwarded-Host' => 'example.test',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/_tests/proxy-root');

        $response->assertOk()->assertSeeText('https://example.test');
    }

    public function test_ignores_forwarded_https_scheme_from_an_untrusted_proxy(): void
    {
        config()->set('trustedproxy.proxies', ['192.0.2.1']);
        Route::get('/_tests/proxy-root', static fn (Request $request): string => $request->root());

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2'])
            ->withHeaders([
                'X-Forwarded-Host' => 'example.test',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/_tests/proxy-root');

        $response->assertOk()->assertSeeText('http://gurujaringan2.localhost');
    }
}
