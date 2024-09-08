<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Illuminate\Support\Facades\Cache;

class TokenStore
{
    protected static function generateCacheKey($refreshUrl): string
    {
        return 'oauth_token_'.str($refreshUrl)->replace(['https://', '/'], [''])->__toString();
    }

    public static function get(
        string $refreshUrl,
        Credentials $credentials,
        array $options = [],
        string $tokenType = 'Bearer',
    ): AccessToken {
        $cacheKey    = static::generateCacheKey($refreshUrl);
        $accessToken = Cache::get($cacheKey);

        if ($accessToken) {
            return $accessToken;
        }

        $accessToken = app(RefreshToken::class)(...func_get_args());
        //$ttl         = $accessToken->getExpiresAt()->diffInSeconds(absolute: true);
        $ttl         = $accessToken->getExpiresIn();

        //dd($cacheKey, $accessToken, $ttl);
        Cache::put($cacheKey, $accessToken, $ttl);

            return $accessToken;
    }
}
