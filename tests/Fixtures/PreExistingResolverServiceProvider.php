<?php

namespace Tests\Fixtures;

use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class PreExistingResolverServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Feature::resolveScopeUsing(fn () => 'tenant-42');
    }
}
