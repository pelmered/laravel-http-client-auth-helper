<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class Credentials
{
    public const AUTH_TYPE_BODY = 'body';

    public const AUTH_TYPE_QUERY = 'query';

    public const AUTH_TYPE_BASIC = 'basic';

    public const AUTH_TYPE_BEARER = 'Bearer';

    public const AUTH_TYPE_CUSTOM = 'custom';

    public const GRANT_TYPE_CLIENT_CREDENTIALS = 'client_credentials';

    public const GRANT_TYPE_PASSWORD_CREDENTIALS = 'password_credentials';

    //TODO: Add support for authorization_code and implicit grants
    public const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';

    public const GRANT_TYPE_IMPLICIT = 'implicit';

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
            $this->authType = self::AUTH_TYPE_BASIC;
        }

        //$this->validate();
    }

    protected function validate(): void
    {
        Validator::make((array) $this, [
            'grantType' => Rule::in([self::GRANT_TYPE_CLIENT_CREDENTIALS, self::GRANT_TYPE_PASSWORD_CREDENTIALS]),
            'authType'  => Rule::in([self::AUTH_TYPE_BEARER, self::AUTH_TYPE_BODY, self::AUTH_TYPE_QUERY, self::AUTH_TYPE_BASIC, self::AUTH_TYPE_CUSTOM]),
        ])->validate();
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
            $this->authType       = self::AUTH_TYPE_CUSTOM;
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
        if ($this->authType === self::AUTH_TYPE_BODY) {
            return $httpClient;
        }
        if ($this->authType === self::AUTH_TYPE_BEARER && $this->token) {
            return $httpClient->withToken($this->token);
        }
        if ($this->authType === self::AUTH_TYPE_BASIC) {
            if (! $this->clientId || ! $this->clientSecret) {
                throw new InvalidArgumentException('Basic auth requires client id and client secret. Check documentation/readme. ');
            }

            return $httpClient->withBasicAuth($this->clientId, $this->clientSecret);
        }
        if ($this->authType === self::AUTH_TYPE_QUERY && $this->token) {
            return $httpClient->withQueryParameters([
                $this->tokenName => $this->token,
            ]);
        }
        if ($this->authType === self::AUTH_TYPE_CUSTOM && is_callable($this->customCallback)) {
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
        if ($this->authType !== self::AUTH_TYPE_BODY) {
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
            $this->authType = self::AUTH_TYPE_BEARER;
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
            $this->authType = self::AUTH_TYPE_BASIC;
        }
    }
}
