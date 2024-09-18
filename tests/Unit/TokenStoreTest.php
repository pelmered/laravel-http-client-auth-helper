<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Illuminate\Support\Facades\Cache;
use Pelmered\LaravelHttpOAuthHelper\AccessToken;
use Pelmered\LaravelHttpOAuthHelper\Credentials;
use Pelmered\LaravelHttpOAuthHelper\Options;
use Pelmered\LaravelHttpOAuthHelper\TokenStore;

it('reads and stores a token in cache be default', function () {

    Cache::clear();

    $cacheKey = 'oauth_token_example.com-oauth-token';

    $cacheManager    = app('cache');
    $cacheManagerSpy = Mockery::spy($cacheManager);
    Cache::swap($cacheManagerSpy);

    $cacheRepository    = Cache::driver(); // or: $cacheRepository = app('cache.store');
    $cacheRepositorySpy = Mockery::spy($cacheRepository);
    Cache::swap($cacheRepositorySpy);

    Cache::shouldReceive('store')->andReturnSelf();
    $cacheRepositorySpy->shouldReceive('put')->times(1);
    $cacheRepositorySpy->shouldReceive('get')
        ->with($cacheKey)
        ->times(1)
        ->andReturn(null);

    $accessToken1 = TokenStore::get(
        'https://example.com/oauth/token',
        new Credentials('my_token'),
        new Options(
            scopes: ['scope1', 'scope2'],
        ),
    );

    $cacheRepositorySpy->shouldReceive('get')
        ->with($cacheKey)
        ->times(2)
        ->andReturn($accessToken1);

    $accessToken2 = TokenStore::get(
        'https://example.com/oauth/token',
        new Credentials('my_token'),
        new Options(
            scopes: ['scope1', 'scope2'],
        ),
    );
    $accessToken3 = TokenStore::get(
        'https://example.com/oauth/token',
        new Credentials('my_token'),
        new Options(
            scopes: ['scope1', 'scope2'],
        ),
    );

    isSameAccessToken($accessToken1, $accessToken2, 0);
    isSameAccessToken($accessToken1, $accessToken3, 0);
});

it('reads and stores a token in cache with custom cache key and driver', function () {

    Cache::clear();

    $cacheStore        = 'file';
    $cacheKey          = 'custom_cache_key';

    $cacheManager    = app('cache');
    $cacheManagerSpy = Mockery::spy($cacheManager);
    Cache::swap($cacheManagerSpy);

    $cacheRepository    = Cache::driver($cacheStore); // or: $cacheRepository = app('cache.store');
    $cacheRepositorySpy = Mockery::spy($cacheRepository);
    Cache::swap($cacheRepositorySpy);

    Cache::shouldReceive('store')->with($cacheStore)->andReturnSelf();
    $cacheRepositorySpy->shouldReceive('put')
        ->with($cacheKey, Mockery::type(AccessToken::class), Mockery::any())
        ->times(1);
    $cacheRepositorySpy->shouldReceive('get')
        ->with($cacheKey)
        ->times(1)
        ->andReturn(null);

    $accessToken1 = TokenStore::get(
        'https://example.com/oauth/token',
        new Credentials(
            clientId: 'this_is_my_client_id',
            clientSecret: 'this_is_my_client_secret',
        ),
        new Options(
            scopes: ['scope1', 'scope2'],
            authType: Credentials::AUTH_TYPE_BASIC,
            cacheKey: $cacheKey,
            cacheDriver: $cacheStore
        ),
    );

    $cacheRepositorySpy->shouldReceive('get')
        ->with($cacheKey)
        ->times(1)
        ->andReturn($accessToken1);

    $accessToken2 = TokenStore::get(
        'https://example.com/oauth/token',
        new Credentials(
            clientId: 'this_is_my_client_id',
            clientSecret: 'this_is_my_client_secret',
        ),
        new Options(
            scopes: ['scope1', 'scope2'],
            authType: Credentials::AUTH_TYPE_BASIC,
            cacheKey: $cacheKey,
            cacheDriver: $cacheStore
        ),
    );

    isSameAccessToken($accessToken1, $accessToken2);
});
it('reads and stores a token in cache with custom cache driver2', function () {

    Cache::clear();

    $cacheStore        = 'file';
    $cacheKey          = 'custom_cache_key';
    $cacheStoreNotUsed = 'array';

    $accessToken = TokenStore::get(
        'https://example.com/oauth/token',
        new Credentials(
            clientId: 'this_is_my_client_id',
            clientSecret: 'this_is_my_client_secret',
        ),
        new Options(
            scopes: ['scope1', 'scope2'],
            authType: Credentials::AUTH_TYPE_BASIC,
            cacheKey: $cacheKey,
            cacheDriver: $cacheStore
        ),
    );

    $accessToken2 = Cache::store($cacheStore)->get($cacheKey);
    $accessToken3 = Cache::store($cacheStoreNotUsed)->get($cacheKey);

    isSameAccessToken($accessToken, $accessToken2);

    expect($accessToken3)->toBeNull();
});
