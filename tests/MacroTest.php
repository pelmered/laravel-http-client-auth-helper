<?php

namespace Pelmered\LaravelHttpOAuthHelper\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class MacroTest extends TestCase
{
    /*
    public function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }
    */

    public function testMacro(): void
    {
        $response = Http::withOAuthToken(
            'my_token',
            'https://example.com/oauth/token',
            'client_id',
            'client_secret',
            ['scopes' => ['scope1', 'scope2']]
        )->get('https://example.com/api');

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer this_is_my_access_token') && $request->url() === 'https://example.com/api';
        });
    }
}
