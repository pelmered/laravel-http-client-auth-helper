<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class RefreshToken
{
    protected PendingRequest $httpClient;

    /**
     * @var array<string, mixed>
     */
    protected array $requestBody = [];

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws Exception
     */
    public function __invoke(
        string $refreshUrl,
        Credentials $credentials,
        Options $options,
    ): AccessToken {

        //TODO: Refactor $options

        //dd($options);
        /*
        $options = array_merge([
            'scopes'       => [],
            'auth_type'    => 'body',
            'expires'      => 3600,
            'access_token' => 'access_token',
        ], $options);
        */

        $this->httpClient = Http::asForm();

        $this->requestBody = [
            'grant_type' => $options->grantType,
            'scope'      => $options->getScopes(),
        ];

        $this->resolveRefreshAuth($credentials);

        $response = $this->httpClient->post($refreshUrl, $this->requestBody);

        return new AccessToken(
            accessToken: $this->getAccessTokenFromResponse($response, $options->accessToken),
            expiresAt: $this->getExpiresAtFromResponse($response, $options->expires),
            //tokenType: $options['auth_type'],
            tokenType: $options->tokenType,
            customCallback: $options->tokenTypeCustomCallback,
        );
    }

    protected function resolveRefreshAuth(Credentials $credentials): void
    {
        $this->httpClient  = $credentials->addAuthToRequest($this->httpClient);
        $this->requestBody = $credentials->addAuthToBody($this->requestBody);
    }

    protected function getAccessTokenFromResponse(Response $response, callable|string $accessTokenOption): string
    {
        return is_callable($accessTokenOption) ? $accessTokenOption($response) : $response->json()[$accessTokenOption];
    }

    protected function getExpiresAtFromResponse(Response $response, callable|string|int|Carbon $expiresOption): Carbon
    {
        $expires = is_callable($expiresOption) ? $expiresOption($response) : $expiresOption;

        if (is_string($expires)) {
            if (isset($response->json()[$expires])) {
                $expires = $response->json()[$expires];
            }

            return Carbon::parse($expires)->subMinute();
        }

        if (is_int($expires)) {
            return Carbon::now()->addSeconds($expires)->subMinute();
        }

        if ($expires instanceof Carbon) {
            return $expires->subMinute();
        }

        throw new InvalidArgumentException('Invalid expires option');
    }
}
