<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class LaravelHttpOAuthHelperServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Http::macro('withOAuthToken', function (
            string $refreshUrl,
            string|array|Credentials $credentials = [
                'refresh_token' => '',
                'client_id'     => '',
                'client_secret' => '',
            ],
            array $options = [],
            string $tokenType = 'Bearer'
        ): PendingRequest {
            //dd($credentials instanceof Credentials ? $credentials : new Credentials($credentials));

            $accessToken = TokenStore::get(
                refreshUrl: $refreshUrl,
                credentials: $credentials instanceof Credentials ? $credentials : new Credentials($credentials),
                options: $options = [],
                tokenType: $tokenType,
            );

            $httpClient = $this;

            // If we get a factory, we can create a new pending request
            if ($httpClient instanceof Factory) {
                $httpClient = $httpClient->createPendingRequest();
            }

            return $accessToken->getHttpClient($httpClient);
        });
    }
}
