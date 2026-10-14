<?php

declare(strict_types=1);

namespace App\Adapters\Http\Presenter;

use App\Domain\Book\Book;

/**
 * The book shape of api-contract.md §3.4.
 *
 * `cover_key` is an opaque key, never a path: to render the cover the client
 * calls `GET /books/{id}/cover`, which returns the binary or 404.
 */
final class BookPresenter
{
    public function detail(Book $book): array
    {
        return [
            'id' => $book->id(),
            'title' => $book->title(),
            'author' => $book->author(),
            'isbn' => $book->isbn()->value,
            'total_copies' => $book->totalCopies()->value,
            'available_copies' => $book->availableCopies()->value,
            'cover_key' => $book->coverKey(),
            'deleted_at' => null === $book->deletedAt()
                ? null
                : $book->deletedAt()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}