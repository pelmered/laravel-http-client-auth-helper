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
        public string $authType = Credentials::AUTH_TYPE_BASIC, //TODO: Which auth type should be default?
        public string $grantType = Credentials::GRANT_TYPE_CLIENT_CREDENTIALS,
        public string $tokenType = AccessToken::TYPE_BEARER,
        public string $tokenName = 'token',
        public int|string|Closure $expires = 3600,
        public string|Closure $accessToken = 'access_token',
        public ?Closure $tokenTypeCustomCallback = null,
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
        Validator::make((array) $this, [
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
                AccessToken::TYPE_BEARER,
                AccessToken::TYPE_QUERY,
                AccessToken::TYPE_CUSTOM,
            ]),
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
            'tokenType'   => AccessToken::TYPE_BEARER,
            'authType'    => Credentials::AUTH_TYPE_BASIC,
            'expires'     => 3600,
            'accessToken' => 'access_token',
        ];
    }
}
