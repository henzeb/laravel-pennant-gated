<?php

declare(strict_types=1);

namespace Henzeb\Pennant\Gated\Drivers;

use Laravel\Pennant\Contracts\CanListStoredFeatures;
use Laravel\Pennant\Contracts\CanSetManyFeaturesForScopes;
use Laravel\Pennant\Contracts\DefinesFeaturesExternally;
use Laravel\Pennant\Contracts\Driver;
use Laravel\Pennant\Contracts\HasFlushableCache;

class GatedDriver implements
    Driver,
    DefinesFeaturesExternally,
    CanListStoredFeatures,
    CanSetManyFeaturesForScopes,
    HasFlushableCache
{
    public function __construct(private readonly Driver $driver)
    {
    }

    public function define(string $feature, callable $resolver): void
    {
        $this->driver->define($feature, $resolver);
    }

    public function defined(): array
    {
        return $this->driver->defined();
    }

    public function definedFeaturesForScope(mixed $scope): array
    {
        if ($this->driver instanceof DefinesFeaturesExternally) {
            return $this->driver->definedFeaturesForScope($scope);
        }

        return array_values($this->defined());
    }

    public function getAll(array $features): array
    {
        $result = [];
        foreach ($features as $feature => $scopes) {
            $result[$feature] = array_map(
                fn(mixed $scope) => $this->get($feature, $scope),
                $scopes
            );
        }

        return $result;
    }

    /**
     * A feature is only active for a given scope when it is also active
     * globally, i.e. the global (null-scoped) value acts as a kill-switch
     * that gates every scoped check.
     */
    public function get(string $feature, mixed $scope): mixed
    {
        $global = $this->driver->get($feature, null);

        if (!$global) {
            return false;
        }

        if($scope === null) {
            return $global;
        }

        return $this->driver->get($feature, $scope);
    }

    public function set(string $feature, mixed $scope, mixed $value): void
    {
        $this->driver->set($feature, $scope, $value);
    }

    public function setForAllScopes(string $feature, mixed $value): void
    {
        $this->driver->setForAllScopes($feature, $value);
    }

    public function setAll(array $features): void
    {
        if ($this->driver instanceof CanSetManyFeaturesForScopes) {
            $this->driver->setAll($features);
            return;
        }

        foreach ($features as $feature) {
            $this->driver->set($feature['feature'], $feature['scope'], $feature['value']);
        }
    }

    public function delete(string $feature, mixed $scope): void
    {
        $this->driver->delete($feature, $scope);
    }

    /**
     * @param array<string>|null $features
     */
    public function purge(?array $features): void
    {
        $this->driver->purge($features);
    }

    public function stored(): array
    {
        if ($this->driver instanceof CanListStoredFeatures) {
            return $this->driver->stored();
        }

        return [];
    }

    public function flushCache(): void
    {
        if ($this->driver instanceof HasFlushableCache) {
            $this->driver->flushCache();
        }
    }
}
