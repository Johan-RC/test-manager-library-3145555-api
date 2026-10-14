<?php

declare(strict_types=1);

namespace App\Domain\Loan;

use App\Domain\Shared\DomainException;

/** CA-06.2 · The same book twice in one loan. Maps to 422 DUPLICATE_BOOK_IN_LOAN. */
final class DuplicateBookInLoan extends DomainException
{
    public function __construct(public readonly string $bookTitle)
    {
        parent::__construct(sprintf('The book "%s" appears more than once in the same loan.', $bookTitle));
    }
}