<?php

namespace Pelmered\LaravelHttpOAuthHelper;

use Carbon\Carbon;
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
        public string $authType = Credentials::AUTH_TYPE_BEARER,
        public string $grantType = Credentials::GRANT_TYPE_CLIENT_CREDENTIALS,
        public string $tokenType = AccessToken::TOKEN_TYPE_BEARER,
        public string $tokenName = 'token',
        public int|string|Closure|Carbon $expires = 3600,
        public string|Closure $accessToken = 'access_token',
        public ?Closure $tokenTypeCustomCallback = null,
        public ?string $cacheKey = null,
        public ?string $cacheDriver = null,
    ) {
        $this->validateOptions();
    }

    /*
    public function __get($name)
    {
        return $this->$name;
    }
    */

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    protected function validateOptions(): void
    {
        // Note: closures can't be checked at this point since we don't have access to the response objects
        Validator::make((array) $this, [
            'scopes.*' => 'string',
            'authType' => Rule::in([
                Credentials::AUTH_TYPE_BEARER,
                Credentials::AUTH_TYPE_BODY,
                Credentials::AUTH_TYPE_QUERY,
                Credentials::AUTH_TYPE_BASIC,
                Credentials::AUTH_TYPE_CUSTOM,
            ]),
            'grantType' => Rule::in([
                Credentials::GRANT_TYPE_CLIENT_CREDENTIALS,
                Credentials::GRANT_TYPE_PASSWORD_CREDENTIALS,
            ]),
            'tokenType' => Rule::in([
                AccessToken::TOKEN_TYPE_BEARER,
                AccessToken::TOKEN_TYPE_QUERY,
                AccessToken::TOKEN_TYPE_CUSTOM,
            ]),
            'tokenName' => 'string',
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
            'grantType'   => Credentials::GRANT_TYPE_CLIENT_CREDENTIALS,
            'tokenType'   => AccessToken::TOKEN_TYPE_BEARER,
            'authType'    => Credentials::AUTH_TYPE_BEARER,
            'expires'     => 3600,
            'accessToken' => 'access_token',
        ];
    }
}
