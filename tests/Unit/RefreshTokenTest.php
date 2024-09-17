<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Carbon\Carbon;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
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
                authType: 'basic'
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

    test('refresh token basic with invalid credentials', function () {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Basic auth requires client id and client secret. Check documentation/readme.');

        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials([
                'token',
            ]),
            new Options(
                scopes: ['scope1', 'scope2'],
                authType: Credentials::AUTH_TYPE_BASIC,
                grantType: 'client_credentials',
            ),
        );
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
                authType: Credentials::AUTH_TYPE_BASIC,
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
                scopes: ['scope1', 'scope2'],
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
                scopes: ['scope1', 'scope2'],
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
            ->and($accessToken->getExpiresIn())->toBeBetween(3535, 3540)
            ->and($accessToken->getExpiresAt())->toBeInstanceOf(Carbon::class)
            ->and($accessToken->getCustomCallback())->toBeNull();
    });

    test('custom token type', function () {

        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials(['my_token']),
            new Options(
                scopes: ['scope1', 'scope2'],
                tokenType: AccessToken::TOKEN_TYPE_CUSTOM,
                tokenTypeCustomCallback: function (PendingRequest $httpClient) {
                    return $httpClient->withHeader('MyCustomAuthHeader', 'my_custom_token');
                }
            ),
        );

        $httpClient = (new Factory)->createPendingRequest();

        $httpClient = $accessToken->getHttpClient($httpClient);

        $options = $httpClient->getOptions();

        expect($options['headers']['MyCustomAuthHeader'])->toBe('my_custom_token');
    });

    test('custom auth token type', function () {

        app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials(function (PendingRequest $httpClient) {
                return $httpClient->withHeader('MyCustomAuthHeader', 'my_custom_token');
            }),
            new Options(
                scopes: ['scope1', 'scope2'],
            ),
        );
        Http::assertSent(static function (Request $request) {
            return $request->hasHeader('MyCustomAuthHeader', 'my_custom_token')
                   && $request->url() === 'https://example.com/oauth/token';
        });
    });

    test('auth type query', function () {

        app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials('my_query_token'),
            new Options(
                scopes: ['scope1', 'scope2'],
                authType: Credentials::AUTH_TYPE_QUERY,
                tokenName: 'custom_token_name',
                accessToken: function (Response $response) {
                    return AccessToken::parseQueryTokenFromResponse($response, 'custom_token_name');
                }
            ),
        );
        Http::assertSent(function (Request $request) {

            $token = AccessToken::parseQueryTokenFromUrl($request->url(), 'custom_token_name');

            expect($token)->toBe('my_query_token');

            return $request->url() === 'https://example.com/oauth/token?custom_token_name=my_query_token';
        });
    });

    test('set token expiry with string key with date', function () {

        $this->clearExistingFakes();

        /** @var Carbon $nowDate */
        $nowDate = Carbon::create(2024, 11, 11, 11);

        Carbon::setTestNow($nowDate);

        Http::fake([
            'https://example.com/oauth/token' => Http::response([
                'token_type'   => 'Bearer',
                'access_token' => 'my_custom_access_token',
                'scope'        => 'scope1 scope2',
                'expires_date' => $nowDate->addHour(),
            ], 200),
        ]);

        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials('my_query_token'),
            new Options(
                scopes: ['scope1', 'scope2'],
                expires: 'expires_date',
            ),
        );

        expect($accessToken->getExpiresAt()->timestamp)->toBe($nowDate->subMinute()->timestamp);
    });

    test('set token expiry with string key with integer', function () {

        /** @var Carbon $nowDate */
        $nowDate = Carbon::create(2024, 11, 11, 11);

        Carbon::setTestNow($nowDate);

        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials('my_query_token'),
            new Options(
                scopes: ['scope1', 'scope2'],
                expires: 'expires_in',
            ),
        );

        expect($accessToken->getExpiresAt()->timestamp)->toBe($nowDate->addSeconds(7200)->subMinute()->timestamp);
    });

    test('set token expiry with carbon object', function () {

        /** @var Carbon $nowDate */
        $nowDate = Carbon::create(2024, 11, 11, 11);

        Carbon::setTestNow($nowDate);

        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials('my_query_token'),
            new Options(
                scopes: ['scope1', 'scope2'],
                expires: Carbon::now()->addHour(),
            ),
        );

        expect($accessToken->getExpiresAt()->timestamp)->toBe($nowDate->addHour()->subMinute()->timestamp);
    });

    test('invalid token expiry', function () {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid expires option');

        app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials('my_query_token'),
            new Options(
                scopes: ['scope1', 'scope2'],
                expires: function () {
                    return new stdClass;
                },
            ),
        );
    });

})->done(assignee: 'pelmered');
