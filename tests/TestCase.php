<?php

namespace Pelmered\LaravelHttpOAuthHelper\Tests;

use Illuminate\Support\Facades\Http;
use Pelmered\LaravelHttpOAuthHelper\LaravelHttpOAuthHelperServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        Http::fake([
            'https://example.com/oauth/token' => Http::response([
                'token_type'   => 'Bearer',
                'access_token' => 'this_is_my_access_token',
                'scope'        => 'scope1 scope2',
                'expires_in'   => 7200,
            ], 200),
            'https://example.com/api' => Http::response([
                'data' => 'some data',
            ], 200),
        ]);
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
