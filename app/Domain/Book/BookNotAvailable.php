<?php

declare(strict_types=1);

namespace App\Domain\Book;

use App\Domain\Shared\DomainException;

/** CA-06.3: no copies available. The adapter maps this to 409 BOOK_NOT_AVAILABLE. */
final class BookNotAvailable extends DomainException
{
    public function __construct(public readonly string $bookTitle)
    {
        parent::__construct(sprintf('No available copies left of "%s".', $bookTitle));
    }
}