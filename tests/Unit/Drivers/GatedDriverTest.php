<?php

use Henzeb\Pennant\Gated\Drivers\GatedDriver;
use Laravel\Pennant\Contracts\CanListStoredFeatures;
use Laravel\Pennant\Contracts\CanSetManyFeaturesForScopes;
use Laravel\Pennant\Contracts\DefinesFeaturesExternally;
use Laravel\Pennant\Contracts\Driver;
use Laravel\Pennant\Contracts\HasFlushableCache;
use Mockery\MockInterface;

covers(GatedDriver::class);

afterEach(function () {
    Mockery::close();
});

it('returns the global value directly when no scope is given', function () {
    $driver = Mockery::mock(Driver::class);
    $driver->shouldReceive('get')->once()->with('my-feature', null)->andReturnTrue();

    $gated = new GatedDriver($driver);

    expect($gated->get('my-feature', null))->toBeTrue();
});

it('returns false for a scope when the feature is globally off', function () {
    $driver = Mockery::mock(Driver::class);
    $driver->shouldReceive('get')->once()->with('my-feature', null)->andReturnFalse();
    $driver->shouldNotReceive('get')->with('my-feature', 'my-scope');

    $gated = new GatedDriver($driver);

    expect($gated->get('my-feature', 'my-scope'))->toBeFalse();
});

it('defers to the scoped value when the feature is globally on', function () {
    $driver = Mockery::mock(Driver::class);
    $driver->shouldReceive('get')->once()->with('my-feature', null)->andReturnTrue();
    $driver->shouldReceive('get')->once()->with('my-feature', 'my-scope')->andReturnTrue();

    $gated = new GatedDriver($driver);

    expect($gated->get('my-feature', 'my-scope'))->toBeTrue();
});

it('passes the scope through to the underlying driver unchanged', function (mixed $scope) {
    $driver = Mockery::mock(Driver::class);
    $driver->shouldReceive('get')->once()->with('my-feature', null)->andReturnTrue();
    $driver->shouldReceive('get')
        ->once()
        ->with('my-feature', Mockery::on(fn(mixed $actual) => $actual === $scope))
        ->andReturnTrue();

    $gated = new GatedDriver($driver);

    expect($gated->get('my-feature', $scope))->toBeTrue();
})->with([
    'string' => ['user:1'],
    'integer' => [42],
    'falsy integer (0)' => [0],
    'float' => [4.2],
    'true' => [true],
    'false' => [false],
    'empty string' => [''],
    'empty array' => [[]],
    'array' => [['id' => 1]],
    'object' => [new stdClass()],
]);

it('treats a null scope as the global check, not as an absent scope', function () {
    $driver = Mockery::mock(Driver::class);
    $driver->shouldReceive('get')->once()->with('my-feature', null)->andReturnTrue();
    $driver->shouldNotReceive('get')->with('my-feature', Mockery::not(null));

    $gated = new GatedDriver($driver);

    expect($gated->get('my-feature', null))->toBeTrue();
});

it('passes an object scope through to getAll() unchanged for every feature', function () {
    $scope = new stdClass();

    $driver = Mockery::mock(Driver::class);
    $driver->shouldReceive('get')->once()->with('feature-a', null)->andReturnTrue();
    $driver->shouldReceive('get')->once()->with('feature-b', null)->andReturnTrue();
    $driver->shouldReceive('get')
        ->twice()
        ->with(Mockery::on(fn(string $feature) => in_array($feature, ['feature-a', 'feature-b'])), Mockery::on(fn(mixed $actual) => $actual === $scope))
        ->andReturnTrue();

    $gated = new GatedDriver($driver);

    expect($gated->getAll(['feature-a' => [$scope], 'feature-b' => [$scope]]))
        ->toBe(['feature-a' => [true], 'feature-b' => [true]]);
});

it('passes an object scope through to set() and delete() unchanged', function () {
    $scope = new stdClass();

    $driver = Mockery::mock(Driver::class);
    $driver->shouldReceive('set')->once()->with('my-feature', Mockery::on(fn(mixed $actual) => $actual === $scope), true);
    $driver->shouldReceive('delete')->once()->with('my-feature', Mockery::on(fn(mixed $actual) => $actual === $scope));

    $gated = new GatedDriver($driver);

    $gated->set('my-feature', $scope, true);
    $gated->delete('my-feature', $scope);
});

it('delegates define, set, setForAllScopes, delete and purge to the underlying driver', function () {
    $driver = Mockery::mock(Driver::class);
    $resolver = fn() => true;

    $driver->shouldReceive('define')->once()->with('my-feature', $resolver);
    $driver->shouldReceive('set')->once()->with('my-feature', 'my-scope', true);
    $driver->shouldReceive('setForAllScopes')->once()->with('my-feature', true);
    $driver->shouldReceive('delete')->once()->with('my-feature', 'my-scope');
    $driver->shouldReceive('purge')->once()->with(['my-feature']);

    $gated = new GatedDriver($driver);

    $gated->define('my-feature', $resolver);
    $gated->set('my-feature', 'my-scope', true);
    $gated->setForAllScopes('my-feature', true);
    $gated->delete('my-feature', 'my-scope');
    $gated->purge(['my-feature']);
});

it('delegates defined() to the underlying driver', function () {
    $driver = Mockery::mock(Driver::class);
    $driver->shouldReceive('defined')->once()->andReturn(['my-feature']);

    $gated = new GatedDriver($driver);

    expect($gated->defined())->toBe(['my-feature']);
});

it('delegates getAll() to get() per feature and scope', function () {
    $driver = Mockery::mock(Driver::class);
    $driver->shouldReceive('get')->twice()->with('my-feature', null)->andReturnTrue();
    $driver->shouldReceive('get')->once()->with('my-feature', 'scope-a')->andReturnTrue();
    $driver->shouldReceive('get')->once()->with('my-feature', 'scope-b')->andReturnFalse();

    $gated = new GatedDriver($driver);

    expect($gated->getAll(['my-feature' => ['scope-a', 'scope-b']]))
        ->toBe(['my-feature' => [true, false]]);
});

it('delegates definedFeaturesForScope() when the underlying driver defines features externally', function () {
    $driver = Mockery::mock(Driver::class . ', ' . DefinesFeaturesExternally::class);
    $driver->shouldReceive('definedFeaturesForScope')->once()->with('my-scope')->andReturn(['my-feature']);

    $gated = new GatedDriver($driver);

    expect($gated->definedFeaturesForScope('my-scope'))->toBe(['my-feature']);
});

it('falls back to defined() for definedFeaturesForScope() when the underlying driver does not define features externally', function () {
    $driver = Mockery::mock(Driver::class);
    $driver->shouldReceive('defined')->once()->andReturn([2 => 'my-feature', 5 => 'other-feature']);

    $gated = new GatedDriver($driver);

    expect($gated->definedFeaturesForScope('my-scope'))->toBe(['my-feature', 'other-feature']);
});

it('delegates setAll() when the underlying driver can set many features for scopes', function () {
    $driver = Mockery::mock(Driver::class . ', ' . CanSetManyFeaturesForScopes::class);
    $features = [['feature' => 'my-feature', 'scope' => 'my-scope', 'value' => true]];
    $driver->shouldReceive('setAll')->once()->with($features);
    $driver->shouldNotReceive('set');

    $gated = new GatedDriver($driver);

    $gated->setAll($features);
});

it('falls back to individual set() calls for setAll() when the underlying driver cannot set many features for scopes', function () {
    $driver = Mockery::mock(Driver::class);
    $features = [['feature' => 'my-feature', 'scope' => 'my-scope', 'value' => true]];
    $driver->shouldReceive('set')->once()->with('my-feature', 'my-scope', true);

    $gated = new GatedDriver($driver);

    $gated->setAll($features);
});

it('delegates stored() when the underlying driver can list stored features', function () {
    $driver = Mockery::mock(Driver::class . ', ' . CanListStoredFeatures::class);
    $driver->shouldReceive('stored')->once()->andReturn(['my-feature']);

    $gated = new GatedDriver($driver);

    expect($gated->stored())->toBe(['my-feature']);
});

it('returns an empty array for stored() when the underlying driver cannot list stored features', function () {
    $driver = Mockery::mock(Driver::class);

    $gated = new GatedDriver($driver);

    expect($gated->stored())->toBe([]);
});

it('delegates flushCache() when the underlying driver has a flushable cache', function () {
    $driver = Mockery::mock(Driver::class . ', ' . HasFlushableCache::class);
    $driver->shouldReceive('flushCache')->once();

    $gated = new GatedDriver($driver);

    $gated->flushCache();
});

it('does nothing for flushCache() when the underlying driver has no flushable cache', function () {
    $driver = Mockery::mock(Driver::class);
    $driver->shouldNotReceive('flushCache');

    $gated = new GatedDriver($driver);

    $gated->flushCache();
});
