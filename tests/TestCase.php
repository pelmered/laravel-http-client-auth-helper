<?php

namespace Pelmered\LaravelHttpOAuthHelper\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Pelmered\LaravelHttpOAuthHelper\AccessToken;
use Pelmered\LaravelHttpOAuthHelper\LaravelHttpOAuthHelperServiceProvider;
use Pelmered\LaravelHttpOAuthHelper\UrlHelper;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        Http::fake(
             function (Request $request) {
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

                if (
                    $request->url() === 'https://example.com/api'
                    && $request->hasHeader('Authorization')
                ) {
                    return Http::response([
                        'data'  => 'some data with bearer token',
                        'token' => $request->header('Authorization')[0],
                    ], 200);
                }

                if (Str::of($request->url())->startsWith('https://example.com/api?token=')) {
                    $token = UrlHelper::parseQueryTokenFromUrl($request->url());

                    return Http::response([
                        'data'  => 'some data with query string token',
                        'token' => $token,
                    ], 200);
                }

                return Http::response([], 200);
            }
        );
    }

    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        tap($app['config'], function (Repository $config) {

            //dd($config->get('cache.stores'));

            // Setup queue database connections.
            $configData = [
                'database.default'               => 'testbench',
                'database.connections.testbench' => [
                    'driver'   => 'sqlite',
                    'database' => ':memory:',
                    'prefix'   => '',
                ],
                'queue.batching.database' => 'testbench',
                'queue.failed.database'   => 'testbench',

                //'cache.stores.array'

            ];

            foreach ($configData as $key => $value) {
                $config->set($key, $value);
            }
        });
    }

    protected function usesMySqlConnection($app)
    {
        $app['config']->set('database.default', 'mysql');
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
