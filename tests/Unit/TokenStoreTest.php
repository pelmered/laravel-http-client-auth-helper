<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pelmered\LaravelHttpOAuthHelper\AccessToken;
use Pelmered\LaravelHttpOAuthHelper\Credentials;
use Pelmered\LaravelHttpOAuthHelper\TokenStore;

it('reads and stores a token in cache', function () {

    Cache::spy();

    $accessToken = TokenStore::get(
        'https://example.com/oauth/token',
        new Credentials(
            authType: Credentials::TYPE_BASIC,
            clientId: 'this_is_my_client_id',
            clientSecret: 'this_is_my_client_secret',
        ),
        [
            'scopes' => ['scope1', 'scope2'],
        ],
        AccessToken::TYPE_QUERY
    );

    Cache::shouldHaveReceived('get')->once()->with('oauth_token_example.comoauthtoken');
    Cache::shouldHaveReceived('put')->once()->with('oauth_token_example.comoauthtoken', $accessToken, 3540);
});
