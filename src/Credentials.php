<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Validator;
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

    private ?Closure $customCallback = null;

    protected ?Options $options;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function __construct(
        string|array|callable $credentials = [],
        protected ?string $token = null,
        protected ?string $clientId = null,
        protected ?string $clientSecret = null,
    ) {
        if (! empty($credentials)) {
            $this->parseCredentialsArray($credentials);
        }

        $this->validate();
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    protected function validate(): void
    {
        Validator::make($this->toArray(), [
            'token'          => 'required_without_all:clientId,clientSecret,customCallback|string|nullable',
            'clientId'       => 'required_with:clientSecret|string|nullable',
            'clientSecret'   => 'required_with:clientId|string|nullable',
            'customCallback' => 'required_without_all:token,clientId,clientSecret|nullable',
        ])->validate();
    }

    public function setOptions(Options $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  string|array<string, mixed>|callable  $credentials
     */
    public function parseCredentialsArray(string|array|callable $credentials): void
    {
        if (is_string($credentials)) {
            $this->setRefreshToken($credentials);

            return;
        }

        if (is_callable($credentials)) {
            //$this->authType       = self::AUTH_TYPE_CUSTOM;
            $this->customCallback = $credentials(...);

            return;
        }

        $credentials = array_filter($credentials);
        $arrayLength = count($credentials);

        if ($arrayLength > 0 && array_is_list($credentials)) {
            match ($arrayLength) {
                1       => $this->setRefreshToken($credentials[0]),
                2       => $this->setClientCredentialsPair($credentials[0], $credentials[1]),
                default => throw new InvalidArgumentException('Invalid credentials. Check documentation/readme.'),
            };

            return;
        }
    }

    public function addAuthToRequest(PendingRequest $httpClient, Options $options): PendingRequest
    {
        if ($options->authType === self::AUTH_TYPE_BODY) {
            return $httpClient;
        }
        if (is_callable($this->customCallback)) {
            return ($this->customCallback)($httpClient);

        }
        if ($this->token) {
            if ($options->authType === self::AUTH_TYPE_QUERY) {
                return $httpClient->withQueryParameters([
                    $options->tokenName => $this->token,
                ]);
            }

            return $httpClient->withToken($this->token, $options->authType);
        }
        if ($options->authType === self::AUTH_TYPE_BASIC) {
            if (! $this->clientId || ! $this->clientSecret) {
                throw new InvalidArgumentException('Basic auth requires client id and client secret. Check documentation/readme. ');
            }

            return $httpClient->withBasicAuth($this->clientId, $this->clientSecret);
        }
        if ($options->authType === self::AUTH_TYPE_CUSTOM && is_callable($this->customCallback)) {
            return ($this->customCallback)($httpClient);
        }

        return $httpClient;
    }

    /**
     * @param  array<string, string>  $requestBody
     * @return array<string, string>
     */
    public function addAuthToBody(array $requestBody, Options $options): array
    {
        if ($options->authType !== self::AUTH_TYPE_BODY) {
            return $requestBody;
        }
        if ($this->clientId && $this->clientSecret) {
            return $requestBody + ['client_id' => $this->clientId, 'client_secret' => $this->clientSecret];
        }
        if ($this->token) {
            return $requestBody + [$options->tokenName => $this->token];
        }

        throw new InvalidArgumentException('Invalid credentials. Check documentation/readme.');
    }

    public function setRefreshToken(string $token): void
    {
        $this->token = $token;
        /*
        if (empty($this->options->authType)) {
            $this->options->authType = self::AUTH_TYPE_BEARER;
        }
        */
    }

    public function setClientCredentialsPair(string $clientId, string $clientSecret): void
    {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;

        /*
        if ($tokenType) {
            //$this->options->authType = $tokenType;
        }

        if (empty($this->authType)) {
            $this->options->authType = self::AUTH_TYPE_BASIC;
        }
        */
    }
}
