<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class AccessToken
{
    const TYPE_BEARER = 'Bearer';
    const TYPE_QUERY = 'query';
    const TYPE_CUSTOM = 'custom';




    public function __construct(protected string $accessToken, protected Carbon $expiresAt, protected string $tokenType = self::TYPE_BEARER) {}

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getExpiresAt(): Carbon
    {
        return $this->expiresAt;
    }

    public function getExpiresIn(): int
    {
        return (int) -round($this->expiresAt->diffInSeconds());
    }

    public function getHttpClient(PendingRequest $httpClient)
    {
        $this->tokenName = 'token';

        return match ($this->tokenType) {
            self::TYPE_BEARER => $httpClient->withToken($this->accessToken),

            self::TYPE_QUERY  => $httpClient->withQueryParameters([$this->tokenName => $this->accessToken]),
            //self::TYPE_CUSTOM => $httpClient  = $options['apply_auth_token']($httpClient),


            /*
            'basic'  => $httpClient->withBasicAuth($clientId, $clientSecret),
            'body'   => $requestBody = $requestBody + ['client_id' => $clientId, 'client_secret' => $clientSecret],
            */
            //'custom' => $httpClient  = $options['apply_auth_token']($httpClient),

            default  => throw new InvalidArgumentException('Invalid auth type')
        };


    }
}
