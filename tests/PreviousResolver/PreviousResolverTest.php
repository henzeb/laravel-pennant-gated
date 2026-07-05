<?php

use Laravel\Pennant\Feature;

beforeEach(function () {
    $this->featureName = 'feature-flag-' . uniqid();
});

it('respects a resolver already registered before the gated provider boots, for the gated store too', function () {
    // Only the global gate is on; the pre-existing resolver's scope ('tenant-42')
    // has not been granted, so unscoped access on the gated store must resolve
    // via that resolver (not be treated as the null/global scope) and be false.
    Feature::store('array')->for(null)->activate($this->featureName);

    expect(Feature::store('gated')->active($this->featureName))->toBeFalse();

    $secondFeature = 'feature-flag-' . uniqid();

    // Now both the global gate and the 'tenant-42' scope are on for a fresh
    // feature, so unscoped access resolving via the pre-existing resolver
    // must see it as active.
    Feature::store('array')->for(null)->activate($secondFeature);
    Feature::store('array')->for('tenant-42')->activate($secondFeature);

    expect(Feature::store('gated')->active($secondFeature))->toBeTrue();
});

it('respects a resolver already registered before the gated provider boots via getAll()', function () {
    Feature::store('array')->for(null)->activate($this->featureName);
    Feature::store('array')->for('tenant-42')->activate($this->featureName);

    $result = Feature::store('gated')->getAll([$this->featureName]);

    expect($result[$this->featureName][0])->toBeTrue();
});
