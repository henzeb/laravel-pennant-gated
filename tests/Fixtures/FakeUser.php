<?php

namespace Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Pennant\Contracts\FeatureScopeSerializeable;

class FakeUser implements Authenticatable, FeatureScopeSerializeable
{
    public function __construct(private readonly int|string $id)
    {
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int|string
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function featureScopeSerialize(): string
    {
        return 'user:'.$this->id;
    }
}
