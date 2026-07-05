<?php

namespace Tests;

use Henzeb\Pennant\Gated\Providers\PennantGatedServiceProvider;
use Laravel\Pennant\PennantServiceProvider;
use Tests\Fixtures\PreExistingResolverServiceProvider;

class PreviousResolverTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PennantServiceProvider::class,
            PreExistingResolverServiceProvider::class,
            PennantGatedServiceProvider::class,
        ];
    }
}
