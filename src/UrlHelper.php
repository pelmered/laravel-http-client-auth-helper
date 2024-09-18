<?php
namespace Pelmered\LaravelHttpOAuthHelper;

use Illuminate\Http\Client\Response;

class UrlHelper
{
    public static function parseQueryTokenFromResponse(Response $response, string $queryKey = 'token'): ?string
    {
        $uri = $response->effectiveUri();

        if (! $uri) {
            return null;
        }

        return self::parseTokenFromQueryString($response->effectiveUri()?->getQuery(), $queryKey);
    }

    public static function parseQueryTokenFromUrl(string $url, string $queryKey = 'token'): ?string
    {
        $queryString = parse_url($url, PHP_URL_QUERY);

        if (! $queryString) {
            return null;
        }

        return self::parseTokenFromQueryString($queryString, $queryKey);
    }

    public static function parseTokenFromQueryString(string $queryString, string $queryKey = 'token'): string|array
    {
        parse_str($queryString, $output);

        return $output[$queryKey];
    }
}
