<?php

namespace Tests\Fixtures;

use Laravel\Pennant\Contracts\FeatureScopeSerializeable;

class SerializableScope implements FeatureScopeSerializeable
{
    public function __construct(private readonly string $id)
    {
    }

    public function featureScopeSerialize(): string
    {
        return "scope:{$this->id}";
    }
}
