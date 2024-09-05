<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RefreshToken
{
    /**
     * @param  array<string, mixed>  $options
     *
     * @throws Exception
     */
    public function __invoke(
        string $cacheKey,
        string $refreshUrl,
        string $clientId,
        string $clientSecret,
        array $options,
        string $tokenType = 'Bearer'
    ): string {
        $options = array_merge([
            'scopes'       => [],
            'auth_type'    => 'body',
            'expires'      => 3600,
            'access_token' => 'access_token',
        ], $options);

        $httpClient = Http::asForm();

        $requestBody = [
            'grant_type' => $options['grant_type'] ?? 'client_credentials',
            'scope'      => implode(' ', $options['scopes']),
        ];

        match ($options['auth_type']) {
            'basic'  => $httpClient->withBasicAuth($clientId, $clientSecret),
            'body'   => $requestBody = $requestBody + ['client_id' => $clientId, 'client_secret' => $clientSecret],
            'custom' => $httpClient = $options['apply_auth_token']($httpClient),
            default  => throw new Exception('Invalid auth type')
        };

        $response = $httpClient->post($refreshUrl, $requestBody);

        $ttl = is_callable($options['expires']) ? $options['expires']($response) : $options['expires'];

        //dd($options, $response, $response->json());
        $accessToken = is_callable($options['access_token']) ? $options['access_token']($response) : $response->json()['access_token'];

        Cache::put($cacheKey, $accessToken, $ttl);

        return $accessToken;
    }
}
