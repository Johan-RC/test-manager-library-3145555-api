<?php

declare(strict_types=1);

namespace App\Domain\Book;

use App\Domain\Shared\DomainException;

/** The author is mandatory. */
final class BookAuthorRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('The author is required.');
    }
}