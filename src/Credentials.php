<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Illuminate\Http\Client\PendingRequest;
use InvalidArgumentException;

class Credentials
{
    const TYPE_BODY = 'body';

    const TYPE_QUERY = 'query';

    const TYPE_BASIC = 'basic';

    const TYPE_BEARER = 'bearer';

    const TYPE_CUSTOM = 'custom';

    private ?\Closure $customCallback;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function __construct(
        string|array|callable $credentials = [],
        protected ?string $token = null,
        protected ?string $clientId = null,
        protected ?string $clientSecret = null,
        protected string $authType = '',
        protected string $tokenName = 'token'
    ) {
        if (! empty($credentials)) {
            $this->parseCredentialsArray($credentials);
        }

        // Which auth type should be default?
        if (empty($this->authType)) {
            $this->authType = self::TYPE_BASIC;
        }
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function parseCredentialsArray(string|array|callable $credentials): void
    {
        if (is_string($credentials)) {
            $this->setRefreshToken($credentials);

            return;
        }

        if ($credentials instanceof \Closure) {
            $this->authType       = self::TYPE_CUSTOM;
            $this->customCallback = $credentials;

            return;
        }

        $credentials = array_filter($credentials);
        $arrayLength = count($credentials);

        if ($arrayLength > 0 && array_is_list($credentials)) {
            match ($arrayLength) {
                1 => $this->setRefreshToken($credentials[0]),
                2, 3 => $this->setClientCredentialsPair($credentials[0], $credentials[1], $credentials[2] ?? $this->authType),
                default => throw new InvalidArgumentException('Invalid credentials. Check documentation/readme. '),
            };

            return;
        }

        if (isset($credentials['client_id'], $credentials['client_secret'])) {
            $this->setClientCredentialsPair($credentials['client_id'], $credentials['client_secret'], $credentials['auth_type'] ?? 'basic');
        }

        if (isset($credentials['refresh_token'])) {
            $this->setRefreshToken($credentials['refresh_token']);
        }
    }

    public function addAuthToRequest(PendingRequest $httpClient): PendingRequest
    {
        if ($this->authType === self::TYPE_BODY) {
            return $httpClient;
        }
        if ($this->authType === self::TYPE_BEARER && $this->token) {
            return $httpClient->withToken($this->token);
        }
        if ($this->authType === self::TYPE_BASIC) {
            if (! $this->clientId || ! $this->clientSecret) {
                throw new InvalidArgumentException('Basic auth requires client id and client secret. Check documentation/readme. ');
            }

            return $httpClient->withBasicAuth($this->clientId, $this->clientSecret);
        }
        if ($this->authType === self::TYPE_QUERY && $this->token) {
            return $httpClient->withQueryParameters([
                $this->tokenName => $this->token,
            ]);
        }
        if ($this->authType === self::TYPE_CUSTOM && is_callable($this->customCallback)) {
            return ($this->customCallback)($httpClient);
        }

        return $httpClient;
    }

    /**
     * @param  array<string, string>  $requestBody
     * @return array<string, string>
     */
    public function addAuthToBody(array $requestBody): array
    {
        if ($this->authType !== self::TYPE_BODY) {
            return $requestBody;
        }
        if ($this->clientId && $this->clientSecret) {
            return $requestBody + ['client_id' => $this->clientId, 'client_secret' => $this->clientSecret];
        }
        if ($this->token) {
            return $requestBody + [$this->tokenName => $this->token];
        }

        throw new InvalidArgumentException('Invalid credentials. Check documentation/readme. ');
    }

    public function setRefreshToken(string $token): void
    {
        $this->token = $token;
        if (empty($this->authType)) {
            $this->authType = self::TYPE_BEARER;
        }
    }

    public function setClientCredentialsPair(string $clientId, string $clientSecret, ?string $tokenType = null): void
    {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;

        if ($tokenType) {
            $this->authType = $tokenType;
        }

        if (empty($this->authType)) {
            $this->authType = self::TYPE_BASIC;
        }
    }
}
