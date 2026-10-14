<?php

declare(strict_types=1);

namespace App\Domain\Book;

use App\Domain\Shared\DomainException;

/** The title is mandatory. */
final class BookTitleRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('The title is required.');
    }
}