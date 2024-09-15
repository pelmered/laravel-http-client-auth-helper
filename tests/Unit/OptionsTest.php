<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Illuminate\Validation\ValidationException;
use Pelmered\LaravelHttpOAuthHelper\AccessToken;
use Pelmered\LaravelHttpOAuthHelper\Credentials;
use Pelmered\LaravelHttpOAuthHelper\Options;

it('validates grantType when creating an option object', function () {
    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $this->expectExceptionMessage('The selected grant type is invalid');

    new Options(
        grantType: 'invalid',
        tokenType: AccessToken::TYPE_BEARER,
    );
});

it('validates tokenType when creating an option object', function () {
    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $this->expectExceptionMessage('The selected token type is invalid');

    new Options(
        grantType: Credentials::GRANT_TYPE_CLIENT_CREDENTIALS,
        tokenType: 'invalid',
    );
});

it('validates authType when creating an option object', function () {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('The selected auth type is invalid');

    new Options(
        authType: 'invalid',
    );
});

it('checks for integers in scopes when creating an option object', function () {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('The scopes.1 field must be a string.');

    new Options(
        scopes: ['valid', 1],
    );
});
it('checks for objects in scopes when creating an option object', function () {

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('The scopes.2 field must be a string.');

    new Options(
        scopes: ['valid', 'also_valid', new stdClass()],
    );
});

it('can create an option object', function () {
    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $this->expectExceptionMessage('The selected token type is invalid');

    new Options(
        grantType: Credentials::GRANT_TYPE_CLIENT_CREDENTIALS,
        tokenType: 'invalid',
    );
});
