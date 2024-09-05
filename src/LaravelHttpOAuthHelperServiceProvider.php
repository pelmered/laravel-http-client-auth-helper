<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class LaravelHttpOAuthHelperServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Http::macro('withOAuthToken', function (
            string $refreshUrl,
            string $clientId,
            string $clientSecret,
            array $options = [],
            string $tokenType = 'Bearer'
        ): PendingRequest {

            $cacheKey = 'oauth_token_'.str($refreshUrl)->replace(['https://', '/'], [''])->__toString();

            $accessToken = Cache::get($cacheKey) ?? app(RefreshToken::class)($cacheKey, ...func_get_args());

            return Http::withToken($accessToken, $tokenType);
        });
    }
}
