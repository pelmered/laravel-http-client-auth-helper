<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Illuminate\Validation\ValidationException;
use Pelmered\LaravelHttpOAuthHelper\Credentials;

it('validates authType when creating a credential object', function () {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('The selected auth type is invalid');

    $credentials = new Credentials(
        authType: 'invalid',
    );

});

it('validates credentials array when creating a credential object', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid credentials. Check documentation/readme.');

    $credentials = new Credentials(
        credentials: [
            'value1',
            'value2',
            'value3',
            'value4',
        ],
    );

});
