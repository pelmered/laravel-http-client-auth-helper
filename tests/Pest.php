<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

use Carbon\Carbon;
use Pelmered\LaravelHttpOAuthHelper\AccessToken;

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeWithin', function ($integer, $acceptableDiff) {
    return $this->toBeBetween($integer-$acceptableDiff, $integer+$acceptableDiff);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{

}
function isSameAccessToken($accessToken1, $accessToken2, int $tolerableExpiryDiff = 0)
{
    expect($accessToken1)->toBeInstanceOf(AccessToken::class)
        ->and($accessToken2)->toBeInstanceOf(AccessToken::class)
        ->and($accessToken1->getAccessToken())->toBe($accessToken2->getAccessToken())
        ->and($accessToken1->getTokenType())->toBe($accessToken2->getTokenType())
        ->and($accessToken1->getExpiresAt())->toBeInstanceOf(Carbon::class)
        ->and($accessToken1->getCustomCallback())->toBe($accessToken1->getCustomCallback());

    $tolerableExpiryDiff >= 0
        ? expect($accessToken1->getExpiresIn())->toBe($accessToken1->getExpiresIn())
            ->and($accessToken1->getExpiresAt()->getPreciseTimestamp(6))->toBe($accessToken2->getExpiresAt()->getPreciseTimestamp(6))
        : expect($accessToken1->getExpiresIn())->toBeWithin($accessToken1->getExpiresIn(), $tolerableExpiryDiff)
            ->and($accessToken1->getExpiresAt()->timestamp)->toBeWithin($accessToken2->getExpiresAt()->timestamp, $tolerableExpiryDiff);
}
