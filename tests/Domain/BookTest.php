<?php

declare(strict_types=1);

use App\Domain\Book\Book;
use App\Domain\Book\BookNotAvailable;
use App\Domain\Book\InvalidIsbn;
use App\Domain\Book\TotalCopiesBelowLent;
use App\Domain\Shared\InvalidQuantity;

/*
 * T-03 · Isbn and Quantity, and T-04 · Book.
 *
 * These tests run with no database, no server and no container. That is the
 * whole point of Art. I, and it is why the evidence for T-03 and T-04 is
 * executable at all.
 */

function aBook(int $total = 3): Book
{
    return Book::register(
        'book-1',
        'One Hundred Years of Solitude',
        'Gabriel García Márquez',
        '9780307474728',
        'cat-1',
        $total,
    );
}

it('accepts exactly 13 digits in the ISBN (CA-03.2)', function () {
    $book = Book::register('b', 'T', 'A', '9780307474728', 'c', 1);

    expect($book->isbn()->value)->toBe('9780307474728');
});

it('rejects an ISBN that is not 13 digits (CA-03.2)', function (string $raw) {
    expect(fn () => Book::register('b', 'T', 'A', $raw, 'c', 1))
        ->toThrow(InvalidIsbn::class);
})->with([
    ['978030747472'],    // 12 digits
    ['97803074747289'],  // 14 digits
    ['97803074747X8'],   // not a digit
    [' 978030747472 '], // not trimmed before validation
    [''],                // empty
]);

it('throws InvalidIsbn outside the domain vocabulary', function () {
    expect(fn () => Book::register('b', 'T', 'A', 'nope', 'c', 1))
        ->toThrow(InvalidIsbn::class);
});

it('refuses a total of copies below one', function () {
    expect(fn () => aBook(0))->toThrow(InvalidQuantity::class);
});

it('starts with the available copies equal to the total (CA-03.1)', function () {
    expect(aBook(4)->availableCopies()->value)->toBe(4);
});

it('never lets the available copies go below zero (T-04 evidence)', function () {
    $book = aBook(1);

    $book->withdraw();

    expect($book->availableCopies()->value)->toBe(0);

    expect(fn () => $book->withdraw())->toThrow(BookNotAvailable::class);
    expect($book->availableCopies()->value)->toBe(0);
});

it('never lets the available copies go above the total (CA-07.3)', function () {
    $book = aBook(2);

    $book->restock();
    $book->restock();

    expect($book->availableCopies()->value)->toBe(2);
    expect(fn () => $book->restock())->toThrow(InvalidQuantity::class);
    expect($book->availableCopies()->value)->toBe(2);
});

it('adjusts the available copies by the same difference when the total changes (CA-03.3)', function () {
    $book = aBook(4);
    $book->withdraw();          // 3 available, 1 on loan

    $book->changeTotalCopies(6);

    expect($book->totalCopies()->value)->toBe(6)
        ->and($book->availableCopies()->value)->toBe(5);
});

it('refuses a total that would drive the available copies below zero (CA-03.3)', function () {
    $book = aBook(4);
    $book->withdraw();
    $book->withdraw();          // 2 available, 2 on loan

    expect(fn () => $book->changeTotalCopies(1))->toThrow(TotalCopiesBelowLent::class);
});

it('allows the total to fall exactly onto the number on loan (CA-03.3)', function () {
    $book = aBook(4);
    $book->withdraw();
    $book->withdraw();          // 2 available, 2 on loan

    $book->changeTotalCopies(2);

    expect($book->availableCopies()->value)->toBe(0)
        ->and($book->totalCopies()->value)->toBe(2);
});

it('soft deletes only when there are no open lines (CA-03.5)', function () {
    $book = aBook(2);
    $now = new DateTimeImmutable('2026-10-14T14:15:00Z');

    expect(fn () => $book->softDelete(1, $now))
        ->toThrow(App\Domain\Book\BookHasOpenLoans::class);
    expect($book->isDeleted())->toBeFalse();

    $book->softDelete(0, $now);

    expect($book->isDeleted())->toBeTrue();
});

it('stores a null cover key rather than an empty string (CA-03.4)', function () {
    $book = aBook(1);

    expect($book->coverKey())->toBeNull();

    $book->withCoverKey('');

    expect($book->coverKey())->toBeNull();

    $book->withCoverKey('covers/9f2a1c.bin');

    expect($book->coverKey())->toBe('covers/9f2a1c.bin');
});