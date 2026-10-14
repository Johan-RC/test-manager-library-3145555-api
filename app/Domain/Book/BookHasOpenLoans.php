<?php

declare(strict_types=1);

namespace App\Domain\Book;

/** CA-03.5: a book with open loans cannot be soft-deleted. Maps to 409. */
final class BookHasOpenLoans extends DomainException
{
    public function __construct(public readonly string $bookTitle)
    {
        parent::__construct(sprintf('The book "%s" has open loans and cannot be deleted.', $bookTitle));
    }
}