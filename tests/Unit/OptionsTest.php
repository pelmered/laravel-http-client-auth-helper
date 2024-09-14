<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Illuminate\Validation\ValidationException;
use Pelmered\LaravelHttpOAuthHelper\AccessToken;
use Pelmered\LaravelHttpOAuthHelper\Credentials;
use Pelmered\LaravelHttpOAuthHelper\Options;

it('validates grantType when creating an option object', function () {
    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $this->expectExceptionMessage('The selected grant type is invalid');

    $options = new Options(
        grantType: 'invalid',
        tokenType: AccessToken::TYPE_BEARER,
    );
});

it('validates tokenType when creating an option object', function () {
    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $this->expectExceptionMessage('The selected token type is invalid');

    $options = new Options(
        grantType: Credentials::GRANT_TYPE_CLIENT_CREDENTIALS,
        tokenType: 'invalid',
    );
});

it('validates authType when creating an option object', function () {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('The selected auth type is invalid');

    $options = new Options(
        authType: 'invalid',
    );
});

it('can create an option object', function () {
    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $this->expectExceptionMessage('The selected token type is invalid');

    $options = new Options(
        grantType: Credentials::GRANT_TYPE_CLIENT_CREDENTIALS,
        tokenType: 'invalid',
    );

    //dd($options->toArray());
});
