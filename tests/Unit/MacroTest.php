<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Illuminate\Cache\FileStore;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pelmered\LaravelHttpOAuthHelper\AccessToken;
use Pelmered\LaravelHttpOAuthHelper\Credentials;

beforeEach(function () {});

test('macro with shorthand refresh token', function () {
    $response = Http::withRefreshToken(
        'https://example.com/oauth/token',
        ['my_refresh_token'],
        ['scopes' => ['scope1', 'scope2']],
    )->get('https://example.com/api');

    Cache::shouldReceive('get')
        ->with('test', '', \Closure::class)
        ->andReturn('');

    Http::assertSent(static function (Request $request) {
        return $request->hasHeader('Authorization', 'Bearer this_is_my_access_token_from_body_refresh_token') && $request->url() === 'https://example.com/api';
    });
});
test('macro with shorthand client credentials', function () {
    $response = Http::withRefreshToken(
        'https://example.com/oauth/token',
        [
            'my_client_id', 'my_client_secret',
        ],
        ['scopes' => ['scope1', 'scope2']],
    )->get('https://example.com/api');

    expect($response->json()['data'])->toBe('some data with bearer token');

    Http::assertSentInOrder([
        function (Request $request) {
            return $request->hasHeader('Authorization', 'Basic bXlfY2xpZW50X2lkOm15X2NsaWVudF9zZWNyZXQ=')
                   && $request->url() === 'https://example.com/oauth/token';
        },
        function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer this_is_my_access_token_from_body_refresh_token')
                   && $request->url() === 'https://example.com/api';
        },
    ]);
});
test('macro with refresh token in credentials object', function () {
    $response = Http::withRefreshToken(
        'https://example.com/oauth/token',
        new Credentials(
            token: 'this_is_my_refresh_token',
        ),
        [
            'scopes'   => ['scope1', 'scope2'],
            'authType' => Credentials::AUTH_TYPE_BEARER,
        ]
    )->get('https://example.com/api');

    expect($response->json()['data'])->toBe('some data with bearer token');

    Http::assertSentInOrder([
        function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer this_is_my_refresh_token') && $request->url() === 'https://example.com/oauth/token';
        },
        function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer this_is_my_access_token_from_body_refresh_token') && $request->url() === 'https://example.com/api';
        },
    ]);
});

test('macro with client credentials in credentials object', function () {
    $response = Http::withRefreshToken(
        'https://example.com/oauth/token',
        new Credentials(
            clientId: 'this_is_my_client_id',
            clientSecret: 'this_is_my_client_secret',
        ),
        [
            'scopes'    => ['scope1', 'scope2'],
            'tokenType' => AccessToken::TYPE_QUERY,
            'authType'  => Credentials::AUTH_TYPE_BASIC,
        ],
    )->get('https://example.com/api');

    Http::assertSentInOrder([
        function (Request $request) {
            return $request->hasHeader('Authorization', 'Basic dGhpc19pc19teV9jbGllbnRfaWQ6dGhpc19pc19teV9jbGllbnRfc2VjcmV0')
                   && $request->url() === 'https://example.com/oauth/token';
        },
        function (Request $request) {
            return $request->url() === 'https://example.com/api?token=this_is_my_access_token_from_body_refresh_token';
        },
    ]);
});

test('macro with custom cache store', function () {

    Cache::clear();
    Cache::spy();

    Cache::shouldReceive('store')
        ->with('file')
        ->andReturn(new FileStore(app()['files'], 'tests/cache'));

    $response = Http::withRefreshToken(
        'https://example.com/oauth/token',
        new Credentials(
            clientId: 'this_is_my_client_id',
            clientSecret: 'this_is_my_client_secret',
        ),
        [
            'scopes'      => ['scope1', 'scope2'],
            'tokenType'   => AccessToken::TYPE_BEARER,
            'authType'    => Credentials::AUTH_TYPE_BASIC,
            'cacheDriver' => 'file',
            'cacheKey'    => 'my_cache_key',
        ],
    )->get('https://example.com/api');

    $data = $response->json();

    expect($data['data'])->toBe('some data with bearer token')
        ->and($data['token'])->toBe('Bearer this_is_my_access_token_from_body_refresh_token');
});
