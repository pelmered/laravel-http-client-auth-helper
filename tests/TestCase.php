<?php

namespace Pelmered\LaravelHttpOAuthHelper\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pelmered\LaravelHttpOAuthHelper\LaravelHttpOAuthHelperServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        Http::fake(
            static function (Request $request) {
                if ($request->url() === 'https://example.com/oauth/token') {
                    if ($request->token = 'my_refresh_token') {
                        return Http::response([
                            'token_type'   => 'Bearer',
                            'access_token' => 'this_is_my_access_token_from_body_refresh_token',
                            'scope'        => implode(' ', $request->data()['scopes'] ?? []),
                            'expires_in'   => 7200,
                        ], 200);
                    }

                    if ($request->hasHeader('Authorization', 'Basic dXNlcjpwYXNzd29yZA==')) {
                        return Http::response([
                            'token_type'   => 'Bearer',
                            'access_token' => 'this_is_my_access_token_from_basic_auth',
                            'scope'        => 'scope1 scope2',
                            'expires_in'   => 7200,
                        ], 200);
                    }
                }

                if ($request->url() === 'https://example.com/api') {
                    return Http::response([
                        'data' => 'some data',
                    ], 200);
                }

                return Http::response([], 200);
            }
        );

        Cache::spy();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelHttpOAuthHelperServiceProvider::class,
        ];
    }

    public static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);

        return $class->getMethod($name)->invokeArgs($obj, $args);
    }

    public static function getProperty($object, $property)
    {
        $reflectedClass = new \ReflectionClass($object);
        $reflection     = $reflectedClass->getProperty($property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    protected function clearExistingFakes(): static
    {
        $reflection = new \ReflectionObject(Http::getFacadeRoot());
        $property   = $reflection->getProperty('stubCallbacks');
        $property->setAccessible(true);
        $property->setValue(Http::getFacadeRoot(), collect());

        return $this;
    }
}
