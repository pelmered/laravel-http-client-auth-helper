<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Options
{
    /**
     * @param  array<string>  $scopes
     */
    final public function __construct(
        public array $scopes = [],
        public string $grantType = 'client_credentials',
        public string $tokenType = AccessToken::TYPE_BEARER,
        public int|string|Closure $expires = 3600,
        public string|Closure $accessToken = 'access_token',
        public ?Closure $tokenTypeCustomCallback = null,
    ) {
        $this->validateOptions();
    }

    protected function validateOptions(): void
    {
        Validator::make((array) $this, [
            'grantType' => Rule::in([Credentials::GRANT_TYPE_CLIENT_CREDENTIALS, Credentials::GRANT_TYPE_PASSWORD_CREDENTIALS]),
            'tokenType' => Rule::in([AccessToken::TYPE_BEARER, AccessToken::TYPE_QUERY, AccessToken::TYPE_CUSTOM]),
        ])->validate();
    }

    public function getScopes(): string
    {
        return implode(' ', $this->scopes);
    }

    /**
     * @param  array<string, mixed>  ...$parameters
     */
    public static function make(...$parameters): static
    {
        $defaults = static::getDefaults();
        $options  = array_merge($defaults, ...$parameters);

        return new static(...$options);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getDefaults(): array
    {
        return [
            'scopes'      => [],
            'grantType'   => 'client_credentials',
            'tokenType'   => AccessToken::TYPE_BEARER,
            'expires'     => 3600,
            'accessToken' => 'access_token',
        ];
    }
}
