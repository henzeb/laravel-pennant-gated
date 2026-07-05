<?php

use Laravel\Pennant\Feature;
use Tests\Fixtures\SerializableScope;

beforeEach(function () {
    $this->featureName = 'feature-flag-' . uniqid();
});

it('is active globally without requiring a scope', function () {
    Feature::store('array')->activate($this->featureName);

    expect(Feature::store('gated')->active($this->featureName))->toBeTrue();
});

it('is inactive for the scope when only the scope (not the global gate) is active', function (string|int|SerializableScope $scope) {
    Feature::store('array')->for($scope)->activate($this->featureName);

    expect(Feature::store('gated')->for($scope)->active($this->featureName))->toBeFalse();
})->with([
    'string scope' => ['user:1'],
    'integer scope' => [42],
    'object scope' => [new SerializableScope('user-1')],
]);

it('is inactive for the scope when only the global gate is active', function (string|int|SerializableScope $scope) {
    Feature::store('array')->activate($this->featureName);

    expect(Feature::store('gated')->for($scope)->active($this->featureName))->toBeFalse();
})->with([
    'string scope' => ['user:1'],
    'integer scope' => [42],
    'object scope' => [new SerializableScope('user-1')],
]);

it('is active for the scope once the feature is enabled globally and for that scope', function (string|int|SerializableScope $scope) {
    Feature::store('array')->activate($this->featureName);
    Feature::store('array')->for($scope)->activate($this->featureName);

    expect(Feature::store('gated')->for($scope)->active($this->featureName))->toBeTrue();
})->with([
    'string scope' => ['user:1'],
    'integer scope' => [42],
    'object scope' => [new SerializableScope('user-1')],
]);

it('returns the scoped data when the feature is active both globally and for the scope', function () {
    Feature::store('array')->activate($this->featureName, ['plan' => 'basic']);
    Feature::store('array')->for('user:1')->activate($this->featureName, ['plan' => 'gold']);

    expect(Feature::store('gated')->for('user:1')->value($this->featureName))->toBe(['plan' => 'gold']);
});

it('returns false for the scope\'s value when only the global gate is active with data', function () {
    Feature::store('array')->activate($this->featureName, ['plan' => 'basic']);

    expect(Feature::store('gated')->for('user:1')->value($this->featureName))->toBeFalse();
});

it('returns the global falsy value instead of the scoped data when the feature is off globally', function () {
    Feature::store('array')->for('user:1')->activate($this->featureName, ['plan' => 'gold']);

    expect(Feature::store('gated')->for('user:1')->value($this->featureName))->toBeFalse();
});

it('returns the global data when no scope is given', function () {
    Feature::store('array')->activate($this->featureName, ['plan' => 'basic']);

    expect(Feature::store('gated')->value($this->featureName))->toBe(['plan' => 'basic']);
});

it('does not fall back to the default scope resolver when explicitly scoped to null', function () {
    Feature::resolveScopeUsing(fn () => 'default-user');

    Feature::store('array')->for(null)->activate($this->featureName);

    expect(Feature::store('gated')->for(null)->active($this->featureName))->toBeTrue();
});

it('does use the default scope resolver', function () {
    Feature::resolveScopeUsing(fn () => 'default-user');

    Feature::store('array')->for(null)->activate($this->featureName);

    expect(Feature::store('gated')->active($this->featureName))->toBeFalse();
});
