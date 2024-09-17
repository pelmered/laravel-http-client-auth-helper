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
     * @throws Exception
     */
    public function __invoke(
        string $refreshUrl,
        Credentials $credentials,
        Options $options,
    ): AccessToken {
        $this->httpClient = Http::asForm();

        $this->requestBody = [
            'grant_type' => $options->grantType,
            'scope'      => $options->getScopes(),
        ];

        $this->resolveRefreshAuth($credentials, $options);

        $response = $this->httpClient->post($refreshUrl, $this->requestBody);

        return new AccessToken(
            accessToken: $this->getAccessTokenFromResponse($response, $options->accessToken),
            expiresAt: $this->getExpiresAtFromResponse($response, $options->expires),
            //tokenType: $options['auth_type'],
            tokenType: $options->tokenType,
            customCallback: $options->tokenTypeCustomCallback,
            tokenName: $options->tokenName,
        );
    }

    protected function resolveRefreshAuth(Credentials $credentials, Options $options): void
    {
        $this->httpClient  = $credentials->addAuthToRequest($this->httpClient, $options);
        $this->requestBody = $credentials->addAuthToBody($this->requestBody, $options);
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

            if (is_int($expires)) {
                return Carbon::now()->addSeconds($expires - 60);
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
