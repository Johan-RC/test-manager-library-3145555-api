<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** CA-03.3: the total number of copies must be at least 1. */
final class InvalidQuantity extends DomainException
{
    public function __construct(public readonly int $value)
    {
        parent::__construct(sprintf('The quantity must be at least 1, got %d.', $value));
    }
}