<?php

namespace Remyb98\ObjectTracker\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Track
{
    public function __construct(
        public ?string $alias = null,
        public ?string $display = null,
    )
    {
    }
}
