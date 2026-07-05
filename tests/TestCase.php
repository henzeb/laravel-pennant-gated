<?php

namespace Tests;

use Henzeb\Pennant\Gated\Providers\PennantGatedServiceProvider;
use Laravel\Pennant\PennantServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PennantServiceProvider::class,
            PennantGatedServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('pennant.stores.array', ['driver' => 'array']);
        $app['config']->set('pennant.stores.gated', ['driver' => 'gated', 'store' => 'array']);
        $app['config']->set('pennant.default', 'gated');
    }
}
