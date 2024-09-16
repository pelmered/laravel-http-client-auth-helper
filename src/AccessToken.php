<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Client\PendingRequest;
use InvalidArgumentException;

final class AccessToken
{
    public const TYPE_BEARER = 'Bearer';

    public const TYPE_QUERY = 'query';

    public const TYPE_CUSTOM = 'custom';

    public function __construct(
        protected string $accessToken,
        protected Carbon $expiresAt,
        protected string $tokenType = self::TYPE_BEARER,
        protected string $tokenName = 'token',
        protected ?Closure $customCallback = null,
    ) {
        if ($tokenType === self::TYPE_CUSTOM && is_null($customCallback)) {
            throw new InvalidArgumentException('customCallback must be set when using AUTH_TYPE_CUSTOM');
        }
    }

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
        return (int) round(Carbon::now()->diffInSeconds($this->expiresAt));
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    public function getCustomCallback(): ?Closure
    {
        return $this->customCallback;
    }

    public function getHttpClient(PendingRequest $httpClient): PendingRequest
    {
        return match ($this->tokenType) {
            self::TYPE_BEARER => $httpClient->withToken($this->accessToken),
            self::TYPE_QUERY  => $httpClient->withQueryParameters([$this->tokenName => $this->accessToken]),
            self::TYPE_CUSTOM => $this->resolveCustomAuth($httpClient),
            default           => throw new InvalidArgumentException('Invalid auth type')
        };
    }

    protected function resolveCustomAuth(PendingRequest $httpClient): PendingRequest
    {
        if (! is_callable($this->customCallback)) {
            throw new InvalidArgumentException('customCallback must be callable');
        }

        return ($this->customCallback)($httpClient);
    }
}
