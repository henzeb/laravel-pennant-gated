# Gated driver for Laravel Pennant

[![Build Status](https://github.com/henzeb/laravel-pennant-gated/workflows/tests/badge.svg)](https://github.com/henzeb/laravel-pennant-gated/actions)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/henzeb/laravel-pennant-gated.svg?style=flat-square)](https://packagist.org/packages/henzeb/laravel-pennant-gated)
[![Total Downloads](https://img.shields.io/packagist/dt/henzeb/laravel-pennant-gated.svg?style=flat-square)](https://packagist.org/packages/henzeb/laravel-pennant-gated)
[![License](https://img.shields.io/packagist/l/henzeb/laravel-pennant-gated)](https://packagist.org/packages/henzeb/laravel-pennant-gated)

This package adds a `gated` driver for [Laravel Pennant](https://laravel.com/docs/master/pennant) that wraps any
other Pennant store and turns its global (unscoped) value into a switch that governs every scope at once, without
ever touching their stored values.

## Why

When you activate a Pennant feature for a scope, it becomes active for that scope immediately. There is no way to
configure who a feature is intended for without also making it live for them right away, and no single switch that
governs every scope a feature has ever been activated for:

```php
Feature::for($betaUser)->activate('new-checkout');

Feature::for($betaUser)->active('new-checkout'); // true, live immediately
```

Pennant does provide `Feature::deactivateForEveryone()` to turn a feature off in a single call, but it does so by
overwriting the stored value for every scope with `false`. The information about which scopes it was active for is
gone. Turning the feature back on for the same scopes means activating each of them again.

The `gated` driver separates these two concerns. A scoped activation records who the feature is *intended* for,
while the global (unscoped) value determines whether that targeting is currently in effect. A scoped check is only
`true` when the feature is active both for that scope and globally:

```php
// Configure who the feature is for. It is not active yet, because the
// global value has not been activated.
Feature::store('gated')->for($betaUser)->activate('new-checkout');
Feature::store('gated')->for($otherUser)->activate('new-checkout');

Feature::store('gated')->for($betaUser)->active('new-checkout'); // false

// Activate the feature globally. Every scope that was already configured
// becomes active immediately, without being activated individually.
Feature::store('gated')->activate('new-checkout');

Feature::store('gated')->for($betaUser)->active('new-checkout');  // true
Feature::store('gated')->for($otherUser)->active('new-checkout'); // true

// Deactivate the feature globally. The scoped configuration is left
// untouched.
Feature::store('gated')->deactivate('new-checkout');

Feature::store('gated')->for($betaUser)->active('new-checkout'); // false

// Activate it globally again. Both scopes are active again, with no
// further changes required.
Feature::store('gated')->activate('new-checkout');

Feature::store('gated')->for($betaUser)->active('new-checkout'); // true
```

Because the `gated` driver is a thin wrapper, it works with whichever store you already use for the feature's
underlying values, such as `database` or `array`, or a custom driver of your own.

## Installation

```bash
composer require henzeb/laravel-pennant-gated
```

Add a `gated` entry to the `stores` section of `config/pennant.php`, pointing `store` at whichever other store
should be gated:

```php
'stores' => [
    'gated' => [
        'driver' => 'gated',
        'store' => 'database',
    ],

    // Pennant's built-in stores, e.g.:
    'database' => [
        'driver' => 'database',
    ],
],
```

## How it works

`get($feature, $scope)` on the underlying driver is checked twice:

```php
get(feature, scope):
    if scope is null:
        return underlying->get(feature, null)   // the global value, unchanged

    if not underlying->get(feature, null):
        return false                            // global kill-switch is off

    return underlying->get(feature, scope)       // otherwise, defer to the scoped value
```

So:

- `Feature::active()` (no scope) checks the global switch itself.
- `Feature::for($user)->active()` is `true` only when the feature is active **both** globally and for `$user`.

This applies to `value()` too, since it shares the same gating: if the feature carries a payload rather than a plain
boolean, the scoped payload is only returned once the global gate is open.

```php
Feature::store('gated')->activate('discount', ['percentage' => 10]);
Feature::store('gated')->for($user)->activate('discount', ['percentage' => 25]);

Feature::store('gated')->for($user)->value('discount'); // ['percentage' => 25]

Feature::store('gated')->deactivate('discount');
Feature::store('gated')->for($user)->value('discount'); // false, the gate is closed
```

Everything else &mdash; `define`, `set`, `setForAllScopes`, `delete`, `purge`, and, where supported by the underlying
driver, `definedFeaturesForScope`, `stored`, `setAll` and `flushCache` &mdash; is delegated straight through to the
underlying store.

### A note on the default scope

By default, Pennant treats an unscoped call (e.g. `Feature::active('discount')`, no `->for(...)`) as shorthand for
"check it for the currently authenticated user". For the gated store, that would be wrong: an unscoped check is
meant to ask about the global switch itself, not about whichever user happens to be logged in. If it fell back to
the authenticated user like every other store does, the global switch would effectively become untestable on its
own.

To prevent that, this package registers its own `Feature::resolveScopeUsing()` callback, so an unscoped check on the
gated store resolves to the global scope instead of the authenticated user. If your application (or another
package) had already registered a scope resolver before this package's service provider boots, that resolver is
respected and used as-is instead.

Because Pennant only allows one such callback at a time, if your application (or another package) registers its own
`Feature::resolveScopeUsing()` *after* this package's provider has booted, it will replace ours entirely, and the
gated store would go back to falling through to the authenticated user for unscoped checks. If you ever see the
gated store's unscoped checks behaving as if they were scoped to the current user, this is the first thing to look
for.

## Testing this package

```bash
composer test
```

## Security

If you discover any security related issues, please email henzeberkheij@gmail.com instead of using the issue tracker.

## Credits

- [Henze Berkheij](https://github.com/henzeb)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
