<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TokenStore
{
    protected static function generateCacheKey(string $refreshUrl): string
    {
        return 'oauth_token_'.Str::of($refreshUrl)->replace(['https://', '/'], [''])->__toString();
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws Exception
     */
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
        $ttl         = $accessToken->getExpiresIn();

        Cache::put($cacheKey, $accessToken, $ttl);

        return $accessToken;
    }
}
