<?php

uses(\Pelmered\LaravelHttpOAuthHelper\Tests\TestCase::class);

use Pelmered\LaravelHttpOAuthHelper\Credentials;

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
