<?php

declare(strict_types=1);

namespace App\Domain\Loan;

use App\Domain\Shared\DomainException;

/** CA-06.1 · A loan carries from 1 to 3 lines. Maps to 422. */
final class InvalidLineCount extends DomainException
{
    public function __construct(public readonly int $count)
    {
        parent::__construct(sprintf('A loan carries from 1 to 3 lines, got %d.', $count));
    }
}