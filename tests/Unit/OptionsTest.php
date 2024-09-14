<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Pelmered\LaravelHttpOAuthHelper\AccessToken;
use Pelmered\LaravelHttpOAuthHelper\Credentials;
use Pelmered\LaravelHttpOAuthHelper\Options;

it('validates grantType when creating an option object', function () {
    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $this->expectExceptionMessage('The selected grant type is invalid');

    $credentials = new Options(
        grantType: 'invalid',
        tokenType: AccessToken::TYPE_BEARER,
    );

});

it('can create an option object', function () {
    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $this->expectExceptionMessage('The selected token type is invalid');

    $credentials = new Options(
        grantType: Credentials::GRANT_TYPE_CLIENT_CREDENTIALS,
        tokenType: 'invalid',
    );

});
