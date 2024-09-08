<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class RefreshToken
{
    protected PendingRequest $httpClient;

    protected array $requestBody = [];

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws Exception
     */
    public function __invoke(
        string $refreshUrl,
        Credentials $credentials,
        array $options = [],
        string $tokenType = AccessToken::TYPE_BEARER
    ): AccessToken {
        $options = array_merge([
            'scopes'       => [],
            'auth_type'    => 'body',
            'expires'      => 3600,
            'access_token' => 'access_token',
        ], $options);

        $this->httpClient = Http::asForm();

        $this->requestBody = [
            'grant_type' => $options['grant_type'] ?? 'client_credentials',
            'scope'      => implode(' ', $options['scopes']),
        ];

        $this->validateOptions($options);
        $this->resolveRefreshAuth($credentials);

        //dd($credentials);

        /*
        match ($options['auth_type']) {
            'basic'  => $httpClient->withBasicAuth($clientId, $clientSecret),
            'body'   => $requestBody = $requestBody + ['client_id' => $clientId, 'client_secret' => $clientSecret],
            'custom' => $httpClient  = $options['apply_auth_token']($httpClient),
            default  => throw new InvalidArgumentException('Invalid auth type')
        };
        */

        $response = $this->httpClient->post($refreshUrl, $this->requestBody);

        //dd($response, $response->json());

        return new AccessToken(
            accessToken: $this->getAccessTokenFromResponse($response, $options['access_token']),
            expiresAt: $this->getExpiresAtFromResponse($response, $options['expires']),
            //tokenType: $options['auth_type'],
            tokenType: $tokenType,
        );
    }

    protected function validateOptions($options): void
    {
        Validator::make($options, [
            'auth_type' => 'in:basic,body,custom',
        ])->validate();
    }

    protected function resolveRefreshAuth(Credentials $credentials)
    {
        $this->httpClient  = $credentials->addAuthToRequest($this->httpClient);
        $this->requestBody = $credentials->addAuthToBody($this->requestBody);
    }

    protected function getAccessTokenFromResponse($response, $accessTokenOption): string
    {
        return is_callable($accessTokenOption) ? $accessTokenOption($response) : $response->json()[$accessTokenOption];
    }

    protected function getExpiresAtFromResponse($response, $expiresOption): Carbon
    {
        $expires = is_callable($expiresOption) ? $expiresOption($response) : $expiresOption;

        if (is_string($expires)) {
            return Carbon::parse($expires)->subMinute();
        }

        if (is_int($expires)) {
            return Carbon::now()->addSeconds($expires - 60);
        }

        if ($expires instanceof Carbon) {
            return $expires->subMinute();
        }

        throw new InvalidArgumentException('Invalid expires option');
    }
}
