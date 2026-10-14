<?php

declare(strict_types=1);

namespace App\Domain\Loan;

use App\Domain\Shared\DomainException;

/** CA-07.2 · Returning an already returned line. Maps to 409 ITEM_ALREADY_RETURNED. */
final class ItemAlreadyReturned extends DomainException
{
    public function __construct(public readonly string $bookTitle)
    {
        parent::__construct(sprintf('The line for "%s" was already returned.', $bookTitle));
    }
}