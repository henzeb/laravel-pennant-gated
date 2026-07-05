<?php

use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;
use Tests\Fixtures\FakeUser;

beforeEach(function () {
    $this->featureName = 'feature-flag-' . uniqid();
});

it('ignores the authenticated user as a default scope for the gated store', function () {
    Auth::guard()->setUser(new FakeUser(1));

    Feature::store('gated')->activate($this->featureName);

    expect(Feature::store('array')->for(null)->active($this->featureName))->toBeTrue()
        ->and(Feature::store('array')->for(new FakeUser(1))->active($this->featureName))->toBeFalse();
});

it('still uses the authenticated user as a default scope for a non-gated store', function () {
    Auth::guard()->setUser(new FakeUser(1));

    Feature::store('array')->activate($this->featureName);

    expect(Feature::store('array')->for(new FakeUser(1))->active($this->featureName))->toBeTrue()
        ->and(Feature::store('array')->for(null)->active($this->featureName))->toBeFalse();
});

it('ignores the authenticated user as a default scope for the gated store via getAll()', function () {
    Auth::guard()->setUser(new FakeUser(1));

    Feature::store('array')->for(null)->activate($this->featureName);

    // Passing an int-keyed feature list (no explicit per-feature scope) makes
    // Decorator::getAll() resolve the default scope internally, the same
    // mechanism active()/activate() go through.
    $result = Feature::store('gated')->getAll([$this->featureName]);

    expect($result[$this->featureName][0])->toBeTrue();
});

it('still uses the authenticated user as a default scope for a non-gated store via getAll()', function () {
    Auth::guard()->setUser(new FakeUser(1));

    Feature::store('array')->for(new FakeUser(1))->activate($this->featureName);

    $result = Feature::store('array')->getAll([$this->featureName]);

    expect($result[$this->featureName][0])->toBeTrue();
});
