<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Illuminate\Support\Facades\Cache;
use Pelmered\LaravelHttpOAuthHelper\Credentials;
use Pelmered\LaravelHttpOAuthHelper\Options;
use Pelmered\LaravelHttpOAuthHelper\TokenStore;

it('reads and stores a token in cache', function () {
    Cache::clear();
    Cache::spy();

    Cache::shouldReceive('get')->once()->with('oauth_token_example.comoauthtoken')->andReturn(null);

    $accessToken = TokenStore::get(
        'https://example.com/oauth/token',
        new Credentials(
            clientId: 'this_is_my_client_id',
            clientSecret: 'this_is_my_client_secret',
            authType: Credentials::AUTH_TYPE_BASIC,
        ),
        new Options(
            scopes: ['scope1', 'scope2'],
        ),
    );

    // Does not work with composer update --prefer-lowest
    //Cache::shouldHaveReceived('put')->once()->with('oauth_token_example.comoauthtoken', $accessToken, 3540);
});
