<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class LaravelHttpOAuthHelperServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Http::macro('withOAuthToken', function (
            string $tokenName,
            string $refreshUrl,
            string $clientId,
            string $clientSecret,
            array $options = [],
            string $tokenType = 'Bearer'
        ): PendingRequest {
            $accessToken = Cache::get('oauth_token_'.$tokenName) ?? app(RefreshToken::class)(...func_get_args());

            return Http::withToken($accessToken, $tokenType);
        });
    }

}
