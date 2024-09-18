<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Illuminate\Support\Facades\Http;
use Pelmered\LaravelHttpOAuthHelper\UrlHelper;

test('UrLHelper parseQueryTokenFromResponse', function (array $params, ?string $result) {
    $response = Http::post(...$params[0]);
    $token    = UrlHelper::parseQueryTokenFromResponse($response, $params[1]);

    expect($token)->toEqual($result);
})->with([
    'normal' => [[['https://example.com/oauth/token?token=this_is_my_access_token_from_url', []], 'token'], 'this_is_my_access_token_from_url'],
    'empty'  => [[['https://example.com/oauth/token?token=', []], 'token'], null],
    'custom' => [[['https://example.com/oauth/token?custom=this_is_my_access_token_from_url', []], 'custom'], 'this_is_my_access_token_from_url'],
    'wrong'  => [[['https://example.com/oauth/token?token=this_is_my_access_token_from_url', []], 'wrong'], null],
]);

test('UrLHelper parseQueryTokenFromUrl', function (array $params, ?string $result) {
    $token = UrlHelper::parseQueryTokenFromUrl(...$params);

    expect($token)->toEqual($result);
})->with([
    'normal' => [['https://example.com/oauth/token?token=this_is_my_access_token_from_url'], 'this_is_my_access_token_from_url'],
    'empty'  => [['https://example.com/oauth/token'], null],
    'custom' => [['https://example.com/oauth/token?custom=this_is_my_access_token_from_url', 'custom'], 'this_is_my_access_token_from_url'],
    'wrong'  => [['https://example.com/oauth/token?token=this_is_my_access_token_from_url', 'wrong'], null],
]);

test('UrLHelper parseTokenFromQueryString', function ($params, $result) {

    $token = UrlHelper::parseTokenFromQueryString(...$params);

    expect($token)->toEqual($result);
})->with([
    'normal'               => [['token=this_is_my_access_token_from_url'], 'this_is_my_access_token_from_url'],
    'invalid query string' => [['something_random'], null],
    'custom key'           => [['key=this_is_another_token_from_url', 'key'], 'this_is_another_token_from_url'],
    'wrong key specified'  => [['key=this_is_another_token_from_url', 'other_key'], null],
]);
