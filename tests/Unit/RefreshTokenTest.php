<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pelmered\LaravelHttpOAuthHelper\AccessToken;
use Pelmered\LaravelHttpOAuthHelper\Credentials;
use Pelmered\LaravelHttpOAuthHelper\Options;
use Pelmered\LaravelHttpOAuthHelper\RefreshToken;

describe('Refresh Token Class', function () {
    test('refresh token basic', function () {
        Cache::clear();
        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials([
                'client_id',
                'client_secret',
            ]),
            new Options(
                scopes: ['scope1', 'scope2'],
                grantType: 'client_credentials',
            ),
        );

        expect($accessToken->getAccessToken())->toEqual('this_is_my_access_token_from_body_refresh_token');
        Http::assertSent(static function (Request $request) {
            return $request->hasHeader('Authorization', 'Basic Y2xpZW50X2lkOmNsaWVudF9zZWNyZXQ=')
                   && $request->url()        === 'https://example.com/oauth/token'
                   && $request['grant_type'] === 'client_credentials'
                   && $request['scope']      === 'scope1 scope2';
        });
    });

    test('refresh token body', function () {
        Cache::clear();
        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials(
                'my_refresh_token',
            ),
            new Options(
                scopes: ['scope1', 'scope2'],
                authType: Credentials::AUTH_TYPE_BODY,
                grantType: 'password_credentials',
            ),
        );

        expect($accessToken->getAccessToken())->toEqual('this_is_my_access_token_from_body_refresh_token');
        Http::assertSent(static function (Request $request) {
            return $request->url()           === 'https://example.com/oauth/token'
                   && $request['grant_type'] === 'password_credentials'
                   && $request['scope']      === 'scope1 scope2'
                   && $request['token']      === 'my_refresh_token';
        });
    });

    test('client pair body', function () {
        Cache::clear();
        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials([
                'my_client_id',
                'my_client_secret',
            ],
            ),
            new Options(
                scopes: ['scope1', 'scope2'],
                authType: Credentials::AUTH_TYPE_BODY,
                grantType: 'password_credentials'
            ),
        );

        expect($accessToken->getAccessToken())->toEqual('this_is_my_access_token_from_body_refresh_token');
        Http::assertSent(static function (Request $request) {
            return $request->url()              === 'https://example.com/oauth/token'
                   && $request['grant_type']    === 'password_credentials'
                   && $request['scope']         === 'scope1 scope2'
                   && $request['client_id']     === 'my_client_id'
                   && $request['client_secret'] === 'my_client_secret';
        });
    });

    test('refresh token custom', function () {
        Cache::clear();

        $callback = fn (PendingRequest $httpClient) => $httpClient->withHeader('Authorization', 'my_custom_token');

        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials(
                fn (PendingRequest $httpClient) => $httpClient->withHeader('Authorization', 'my_custom_token'),
            ),
            new Options(
                scopes: ['scope1', 'scope2'],
                grantType: 'password_credentials',
                tokenType: AccessToken::TOKEN_TYPE_CUSTOM,
                tokenTypeCustomCallback: $callback,
            ),
        );

        expect($accessToken->getAccessToken())->toEqual('this_is_my_access_token_from_body_refresh_token')
            ->and($accessToken->getCustomCallback())->toEqual($callback);
        Http::assertSent(static function (Request $request) {
            return $request->url() === 'https://example.com/oauth/token'
                   && $request->hasHeader('Authorization', 'my_custom_token')
                   && $request['grant_type'] === 'password_credentials'
                   && $request['scope']      === 'scope1 scope2';
        });
    });

    test('refresh token with expiry', function () {
        Cache::spy();

        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials([
                'my_client_id',
                'my_client_secret',
            ]),
            new Options(
                scopes: ['scope1', 'scope2'],
                authType: Credentials::AUTH_TYPE_BODY,
                expires: 300,
            ),
        );

        expect($accessToken->getAccessToken())->toEqual('this_is_my_access_token_from_body_refresh_token');
        expect($accessToken->getExpiresIn())->toBeBetween(235, 240);
        Http::assertSent(static function (Request $request) {
            return $request->url()              === 'https://example.com/oauth/token'
                   && $request['grant_type']    === 'client_credentials'
                   && $request['scope']         === 'scope1 scope2'
                   && $request['client_id']     === 'my_client_id'
                   && $request['client_secret'] === 'my_client_secret';
        });
    });

    test('refresh token with expiry callback', function () {
        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials([
                'my_client_id',
                'my_client_secret',
            ]),
            new Options(
                scopes: ['scope1', 'scope2'],
                expires: static function ($response) {
                    return $response->json()['expires_in'];
                },
            ),
        );

        expect($accessToken->getAccessToken())->toEqual('this_is_my_access_token_from_body_refresh_token');
        Http::assertSent(static function (Request $request) {
            return $request->url() === 'https://example.com/oauth/token' && $request['grant_type'] === 'client_credentials' && $request['scope'] === 'scope1 scope2';
        });
    });

    test('get access token from custom key', function () {
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
            'https://example.com/oauth/token',
            new Credentials([
                'my_client_id',
                'my_client_secret',
            ]),
            new Options(
                scopes: ['scope1', 'scope2'],
                accessToken: static function ($response) {
                    return $response->json()['custom_access_token'];
                },
            ),
        );

        expect($accessToken->getAccessToken())->toEqual('my_custom_access_token');

        Http::assertSent(static function (Request $request) {
            return $request->url() === 'https://example.com/oauth/token'
                   && $request->hasHeader('Authorization', 'Basic bXlfY2xpZW50X2lkOm15X2NsaWVudF9zZWNyZXQ=')
                   && $request['grant_type'] === 'client_credentials'
                   && $request['scope']      === 'scope1 scope2';
        });
    });

    test('throws exception with invalid credentials', function () {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credentials. Check documentation/readme.');

        app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials([
                'my_client_id',
                'my_client_secret',
                'invalid',
            ]),
            new Options(
                scopes: ['scope1', 'scope2'],
            ),
        );
    });

    test('throws exception with an invalid auth type', function () {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('The selected auth type is invalid.');

        app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials([
                'my_client_id',
                'my_client_secret',
            ]),
            new Options(
                scopes:   ['scope1', 'scope2'],
                authType: 'invalid',
            ),
        );
    });

    test('throws exception with an invalid token type', function () {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('customCallback must be set when using AUTH_TYPE_CUSTOM');

        app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials(['my_token']),
            new Options(
                scopes:    ['scope1', 'scope2'],
                tokenType: AccessToken::TOKEN_TYPE_CUSTOM,
            ),
        );
    });


    test('access token getters', function () {

        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials(['my_token']),
            new Options(
                scopes: ['scope1', 'scope2'],
            ),
        );

        expect($accessToken->getAccessToken())->toBe('this_is_my_access_token_from_body_refresh_token')
            ->and($accessToken->getExpiresIn())->toBe(3540)
            ->and($accessToken->getExpiresAt())->toBeInstanceOf(Carbon::class)
            ->and($accessToken->getCustomCallback())->toBeNull();
    });

})->done(assignee: 'pelmered');
