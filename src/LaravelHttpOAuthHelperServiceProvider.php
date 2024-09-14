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
        Http::macro('withRefreshToken', function (
            string $refreshUrl,
            string|array|Credentials $credentials = [
                'refresh_token' => '',
                'client_id'     => '',
                'client_secret' => '',
            ],
            array|Options $options = [],
        ): PendingRequest {

            $options     = $options instanceof Options ? $options : Options::make($options);
            $credentials = $credentials instanceof Credentials ? $credentials : new Credentials($credentials);

            $accessToken = TokenStore::get(
                refreshUrl: $refreshUrl,
                credentials: $credentials->setOptions($options),
                options: $options,
            );

            /** @var PendingRequest|Factory $httpClient */
            $httpClient = $this;

            // If we get a factory, we can create a new pending request
            if ($httpClient instanceof Factory) {
                $httpClient = $httpClient->createPendingRequest();
            }

            return $accessToken->getHttpClient($httpClient);
        });
    }
}
