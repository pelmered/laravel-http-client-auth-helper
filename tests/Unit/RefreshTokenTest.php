<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pelmered\LaravelHttpOAuthHelper\Credentials;
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
            [
                'scopes'    => ['scope1', 'scope2'],
                'auth_type' => 'basic',
            ]
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
                authType: Credentials::TYPE_BODY
            ),
            [
                'grant_type' => 'password_credentials',
                'scopes'     => ['scope1', 'scope2'],
                'auth_type'  => 'body',
            ]
        );

        expect($accessToken->getAccessToken())->toEqual('this_is_my_access_token_from_body_refresh_token');
        Http::assertSent(static function (Request $request) {
            //dd($request);
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
                authType: Credentials::TYPE_BODY
            ),
            [
                'grant_type' => 'password_credentials',
                'scopes'     => ['scope1', 'scope2'],
                'auth_type'  => 'body',
            ]
        );

        expect($accessToken->getAccessToken())->toEqual('this_is_my_access_token_from_body_refresh_token');
        Http::assertSent(static function (Request $request) {
            //dd($request);
            return $request->url()              === 'https://example.com/oauth/token'
                   && $request['grant_type']    === 'password_credentials'
                   && $request['scope']         === 'scope1 scope2'
                   && $request['client_id']     === 'my_client_id'
                   && $request['client_secret'] === 'my_client_secret';
        });
    });

    test('refresh token custom', function () {
        Cache::clear();
        $accessToken = app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials(
                fn (PendingRequest $httpClient) => $httpClient->withHeader('Authorization', 'my_custom_token'),
            ),
            options: [
                'grant_type' => 'password_credentials',
                'scopes'     => ['scope1', 'scope2'],
                'auth_type'  => 'custom',
            ]
        );

        expect($accessToken->getAccessToken())->toEqual('this_is_my_access_token_from_body_refresh_token');
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
            ],
                authType: Credentials::TYPE_BODY
            ),
            [
                'scopes'  => ['scope1', 'scope2'],
                'expires' => 300,
                //'auth_type' => Credentials::TYPE_BODY,
            ]
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
            [
                'scopes'  => ['scope1', 'scope2'],
                'expires' => static function ($response) {
                    return $response->json()['expires_in'];
                },
            ]
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
            [
                'scopes'       => ['scope1', 'scope2'],
                'access_token' => static function ($response) {
                    return $response->json()['custom_access_token'];
                },
            ]
        );

        expect($accessToken->getAccessToken())->toEqual('my_custom_access_token');

        Http::assertSent(static function (Request $request) {
            return $request->url() === 'https://example.com/oauth/token'
                   && $request->hasHeader('Authorization', 'Basic bXlfY2xpZW50X2lkOm15X2NsaWVudF9zZWNyZXQ=')
                   && $request['grant_type'] === 'client_credentials'
                   && $request['scope']      === 'scope1 scope2';
        });
    });

    test('throws exception with an invalid auth type', function () {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('The selected auth type is invalid');

        app(RefreshToken::class)(
            'https://example.com/oauth/token',
            new Credentials([
                'my_client_id',
                'my_client_secret',
            ]),
            [
                'scopes'    => ['scope1', 'scope2'],
                'auth_type' => 'invalid',
            ]
        );
    });

})->done(assignee: 'pelmered');