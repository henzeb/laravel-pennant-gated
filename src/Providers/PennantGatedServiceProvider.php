<?php

declare(strict_types=1);

namespace Henzeb\Pennant\Gated\Providers;

use Henzeb\Pennant\Gated\Drivers\GatedDriver;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Laravel\Pennant\FeatureManager;

class PennantGatedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Feature::extend('gated', function ($app, array $config) {
            return new GatedDriver(Feature::driver($config['store']));
        });

        $manager = Feature::getFacadeRoot();

        $previousResolver = \Closure::bind(
            fn () => $this->defaultScopeResolver,
            $manager,
            FeatureManager::class
        )();

        // Borrows Pennant's own built-in fallback (guard()->user()) instead of
        // reimplementing it, by temporarily clearing the resolver, asking
        // FeatureManager's own protected method to build+run that branch,
        // then restoring our resolver.
        $builtInDefault = \Closure::bind(
            function (string $driver) {
                $current = $this->defaultScopeResolver;
                $this->defaultScopeResolver = null;

                try {
                    return $this->defaultScopeResolver($driver)();
                } finally {
                    $this->defaultScopeResolver = $current;
                }
            },
            $manager,
            FeatureManager::class
        );

        Feature::resolveScopeUsing(function (string $driver) use ($previousResolver, $builtInDefault) {
            if ($previousResolver !== null) {
                return $previousResolver($driver);
            }

            if ($driver === 'gated') {
                return null;
            }

            return $builtInDefault($driver);
        });
    }
}
