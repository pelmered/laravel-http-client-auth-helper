<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Carbon\Carbon;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\FileStore;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Pelmered\LaravelHttpOAuthHelper\AccessToken;
use Pelmered\LaravelHttpOAuthHelper\Credentials;
use Pelmered\LaravelHttpOAuthHelper\Options;
use Pelmered\LaravelHttpOAuthHelper\TokenStore;

beforeEach(function () {
    //DefineEnvironment::

});

it('reads and stores a token in cache be default', function () {
    Cache::clear();

    /** @var Carbon $nowDate */
    $nowDate = Carbon::create(2024, 11, 11, 11);

    Carbon::setTestNow($nowDate);

    $cacheBefore = Cache::get('oauth_token_example.comoauthtoken');

    $accessToken1 = TokenStore::get(
        'https://example.com/oauth/token',
        new Credentials('my_token'),
        new Options(
            scopes: ['scope1', 'scope2'],
        ),
    );

    $cacheAfterOne = Cache::get('oauth_token_example.comoauthtoken');

    Carbon::setTestNow($nowDate->addHour());

    $accessToken2 = TokenStore::get(
        'https://example.com/oauth/token',
        new Credentials('my_token'),
        new Options(
            scopes: ['scope1', 'scope2'],
        ),
    );

    expect($cacheBefore)->toBeNull();

    isSameAccessToken($accessToken1, $cacheAfterOne);

    isSameAccessToken($accessToken1, $accessToken2);

});

it('reads and stores a token in cache with custom cache driver', function () {

    Cache::clear();
    //Cache::spy();

    //FileStore::

    //Cache::shouldReceive('get')->once()->with('oauth_token_example.comoauthtoken')->andReturn(null);

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

    expect($accessToken->getAccessToken())->toBe($accessToken2->getAccessToken())
        ->and($accessToken->getExpiresIn())->toBe($accessToken2->getExpiresIn())
        ->and($accessToken->getTokenType())->toBe($accessToken2->getTokenType())
        ->and($accessToken->getTokenName())->toBe($accessToken2->getTokenName())
        ->and($accessToken3)->toBeNull();
});
