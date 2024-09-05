<?php

namespace Pelmered\LaravelHttpOAuthHelper\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pelmered\LaravelHttpOAuthHelper\RefreshToken;

class RefreshTokenTest extends TestCase
{
    public function testRefreshTokenBasic(): void
    {
        Cache::clear();
        $accessToken = app(RefreshToken::class)('my_token', 'https://example.com/oauth/token', 'client_id', 'client_secret', ['scopes' => ['scope1', 'scope2']]);

        $this->assertEquals('this_is_my_access_token', $accessToken);
        Http::assertSent(static function (Request $request) {
            return $request->hasHeader('Authorization', 'Basic Y2xpZW50X2lkOmNsaWVudF9zZWNyZXQ=') && $request->url() === 'https://example.com/oauth/token' && $request['grant_type'] === 'client_credentials' && $request['scope'] === 'scope1 scope2';

        });
    }

    public function testRefreshTokenBody(): void
    {
        Cache::clear();
        $accessToken = app(RefreshToken::class)(
            'my_token',
            'https://example.com/oauth/token',
            'my_client_id',
            'my_client_secret',
            [
                'grant_type' => 'password_credentials',
                'scopes'     => ['scope1', 'scope2'],
                'auth_type'  => 'body',
            ]
        );

        $this->assertEquals('this_is_my_access_token', $accessToken);
        Http::assertSent(static function (Request $request) {
            return $request->url() === 'https://example.com/oauth/token' && $request['grant_type'] === 'password_credentials' && $request['scope'] === 'scope1 scope2' && $request['client_id'] === 'my_client_id' && $request['client_secret'] === 'my_client_secret';
        });
    }

    public function testRefreshTokenWithExpiry()
    {
        Cache::spy();

        $accessToken = app(RefreshToken::class)(
            'my_token',
            'https://example.com/oauth/token',
            'my_client_id',
            'my_client_secret',
            [
                'scopes'  => ['scope1', 'scope2'],
                'expires' => 60,
            ]
        );

        $this->assertEquals('this_is_my_access_token', $accessToken);
        Http::assertSent(static function (Request $request) {
            return $request->url() === 'https://example.com/oauth/token' && $request['grant_type'] === 'client_credentials' && $request['scope'] === 'scope1 scope2';
        });

        Cache::shouldHaveReceived('put')->once()->with('oauth_token_my_token', 'this_is_my_access_token', 60);
    }

    public function testRefreshTokenWithExpiryCallback()
    {
        Cache::spy();

        $accessToken = app(RefreshToken::class)(
            'my_token',
            'https://example.com/oauth/token',
            'my_client_id',
            'my_client_secret',
            [
                'scopes'  => ['scope1', 'scope2'],
                'expires' => static function ($response) {
                    return $response->json()['expires_in'];
                },
            ]
        );

        $this->assertEquals('this_is_my_access_token', $accessToken);
        Http::assertSent(static function (Request $request) {
            return $request->url() === 'https://example.com/oauth/token' && $request['grant_type'] === 'client_credentials' && $request['scope'] === 'scope1 scope2';
        });

        Cache::shouldHaveReceived('put')->once()->with('oauth_token_my_token', 'this_is_my_access_token', 7200);
    }

    public function testGetAccessTokenFromCustomKey()
    {
        $this->clearExistingFakes();
        Http::fake([
            'https://example.com/oauth/token' => Http::response([
                'token_type'          => 'Bearer',
                'custom_access_token' => 'my_custom_access_token',
                'scope'               => 'scope1 scope2',
                'expires_in'          => 7200,
            ], 200),
        ]);

        Cache::spy();
        $accessToken = app(RefreshToken::class)(
            'my_token',
            'https://example.com/oauth/token',
            'my_client_id',
            'my_client_secret',
            [
                'scopes'       => ['scope1', 'scope2'],
                'access_token' => static function ($response) {
                    return $response->json()['custom_access_token'];
                },
            ]
        );

        $this->assertEquals('my_custom_access_token', $accessToken);

        Http::assertSent(static function (Request $request) {
            return $request->url() === 'https://example.com/oauth/token' && $request['grant_type'] === 'client_credentials' && $request['scope'] === 'scope1 scope2';
        });

        Cache::shouldHaveReceived('put')->once()->with('oauth_token_my_token', 'my_custom_access_token', 3600);
    }
}
