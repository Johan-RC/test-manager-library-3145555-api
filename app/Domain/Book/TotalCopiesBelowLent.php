<?php

declare(strict_types=1);

namespace App\Domain\Book;

use App\Domain\Shared\DomainException;

/**
 * CA-03.3 · The new total would push the available copies below zero.
 * Maps to 422 VALIDATION_ERROR.
 */
final class TotalCopiesBelowLent extends DomainException
{
    public function __construct(
        public readonly int $newTotal,
        public readonly int $lent,
    ) {
        parent::__construct(sprintf(
            'The total cannot be %d because %d copies are currently on loan; available copies would go below zero.',
            $newTotal,
            $lent,
        ));
    }
}