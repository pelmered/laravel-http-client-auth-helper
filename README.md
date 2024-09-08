# Laravel HTTP Client OAuth2 helper

An easy-to-use helper for Laravel HTTP Client to make OAuth2 requests.\
Refreshes and caches access tokens automatically with minimal boilerplate code right in the [Laravel HTTP Client](https://laravel.com/docs/11.x/http-client).

[![Latest Stable Version](https://poser.pugx.org/pelmered/laravel-http-oauth-helper/v/stable)](https://packagist.org/packages/pelmered/laravel-http-oauth-helper)
[![Total Downloads](https://poser.pugx.org/pelmered/laravel-http-oauth-helper/d/total)](//packagist.org/packages/pelmered/laravel-http-oauth-helper)
[![Monthly Downloads](https://poser.pugx.org/pelmered/laravel-http-oauth-helper/d/monthly)](//packagist.org/packages/pelmered/laravel-http-oauth-helper)
[![License](https://poser.pugx.org/pelmered/laravel-http-oauth-helper/license)](https://packagist.org/packages/pelmered/laravel-http-oauth-helper)

[![Tests](https://github.com/pelmered/laravel-http-oauth-helper/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/pelmered/laravel-http-oauth-helper/actions/workflows/tests.yml)
[![Build Status](https://scrutinizer-ci.com/g/pelmered/laravel-http-oauth-helper/badges/build.png?b=main)](https://scrutinizer-ci.com/g/pelmered/laravel-http-oauth-helper/build-status/main)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pelmered/laravel-http-oauth-helper/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/pelmered/laravel-http-oauth-helper/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/pelmered/laravel-http-oauth-helper/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/pelmered/laravel-http-oauth-helper/?branch=main)

[![Tested on PHP 8.1 to 8.4](https://img.shields.io/badge/Tested%20on%20PHP-8.1%20|%208.2%20|%208.3%20|%208.4-brightgreen.svg?maxAge=2419200)](https://github.com/pelmered/filament-money-field/actions/workflows/tests.yml)
[![Tested on OS:es Linux, MacOS, Windows](https://img.shields.io/badge/Tested%20on%20lastest%20versions%20of-%20Ubuntu%20|%20MacOS%20|%20Windows-brightgreen.svg?maxAge=2419200)](https://github.com/pelmered/laravel-http-oauth-helper/actions/workflows/tests.yml)

## Requirements

- PHP 8.1 or higher
- Laravel 10

## Contributing

See [Contribution Guide](.github/CONTRIBUTING.md) before sending pull requests.

### Issues & Bug Reports

When you are submitting issues, I appreciate if you could provide a failing test case. That makes my job a lot easier.\
I will try to fix reported issues as soon as possible, but I do this in my spare time, so I might not be able to do it immediately.

## Installation

```bash
composer require pelmered/laravel-http-oauth-helper
```

## Usage

It's really simple to use. Just add the `withOAuthToken` method to your HTTP request and provide the necessary parameters. No configuration needed.

Minimal example:
```php
$response = Http::withOAuthToken(
  'https://example.com/token.oauth2',
  'client_id',
  'client_secret',
)->get(
  'https://example.com/api',
);
```

All parameters with default values:
```php
$response = Http::withOAuthToken(
  'https://example.com/token.oauth2',
  'client_id',
  'client_secret',
  [
    'scopes' => [],
    'expires' => 'expires_in', // When token should be considered expired. A string key in the response JSON for the expiration. We try to parse different formats and then remove 1 minute to be on the safe side.
    'auth_type' => 'body', // 'body' or 'header'
    'access_token' => 'access_token', // Key for the access token in the response JSON
  ],
  'Bearer'
)->get(
  'https://example.com/api',
);
```

You can also provide callbacks for `expires`, `auth_type`, and `access_token` to customize the behavior.
```php
$response = Http::withOAuthToken(
  'https://example.com/token.oauth2',
  'client_id',
  'client_secret',
  [
    'expires' => fn($response) => $response->json()['expires_in'] - 300, // Should return the ttl in seconds that has been parsed from the response and can be manipulated as you want.
    'access_token' => fn($response) => $response->access_token, // Should return the access token that has been parsed from the response.
  ],
  'Bearer'
)->get(
  'https://example.com/api',
);
```

Custom auth for refreshing token:

```php
use Illuminate\Http\Client\PendingRequest;

$response = Http::withOAuthToken(
  'https://example.com/token.oauth2',
  'client_id',
  'client_secret',
  [
    'expires' => fn($response) => $response->json()['expires_in'] - 300, // Should return the ttl in seconds that has been parsed from the response and can be manipulated as you want.
    'access_token' => fn($response) => $response->access_token, // Should return the access token that has been parsed from the response.
    'auth_type' => 'custom',
    'apply_auth_token' => fn(PendingRequest $httpClient) => $request->withHeader('Authorization', 'Bearer ' . $token),
)->get(
  'https://example.com/api',
);
```





### Tips

If you use the same token on multiple places you can create the client only once and save it. For example:
```php
$this->client = Http::withOAuthToken(
  'https://example.com/token.oauth2',
  'client_id',
  'client_secret',
  [
    'scopes' => ['read:posts', 'write:posts', 'read:comments'],
  ]
)->baseUrl('https://example.com/api');
```

to use it later like:
```php
$this->client->get('posts');

$this->client->get('comments');

$this->client->post('posts', [
  'title' => 'My post',
  'content' => 'My content',
]);
```

You can also resolve it in the container if you want.
In your service provider:
```php
$this->app->singleton('my-oauth-client', function ($app) {
  return Http::withOAuthToken(
    'https://example.com/token.oauth2',
    'client_id',
    'client_secret',
    [
      'scopes' => ['read:posts', 'write:posts', 'read:comments'],
    ]
  )->baseUrl('https://example.com/api');
});
```

Then use it like:
```php
app('my-oauth-client')->get('posts');
```
