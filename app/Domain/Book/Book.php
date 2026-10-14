<?php

declare(strict_types=1);

namespace App\Domain\Book;

use App\Domain\Shared\InvalidQuantity;
use App\Domain\Shared\Quantity;

/**
 * T-04 · Book — an aggregate root.
 *
 * Invariants it defends (architecture.md §3):
 *   - available copies stay between 0 and total
 *   - total >= 1
 *   - a soft delete is reversible in the sense that it is only a flag: the
 *     book keeps existing for the loans that reference it (ADR-003)
 *
 * This class imports nothing from Laravel, from Eloquent or from any
 * persistence concern. That is Article I, and it is what lets the invariants
 * be tested with no database, no server and no container.
 */
final class Book
{
    private function __construct(
        private readonly string $id,
        private string $title,
        private string $author,
        private readonly Isbn $isbn,
        private readonly string $categoryId,
        private Quantity $totalCopies,
        private Quantity $availableCopies,
        private ?string $coverKey,
        private ?\DateTimeImmutable $deletedAt,
    ) {
    }

    public static function register(
        string $id,
        string $title,
        string $author,
        string $isbn,
        string $categoryId,
        int $totalCopies,
    ): self {
        $title = trim($title);
        $author = trim($author);

        if ('' === $title) {
            throw new BookTitleRequired();
        }

        if ('' === $author) {
            throw new BookAuthorRequired();
        }

        $total = Quantity::of($totalCopies);

        // CA-03.1: the available copies start equal to the total.
        return new self(
            $id,
            $title,
            $author,
            Isbn::fromString($isbn),
            $categoryId,
            $total,
            $total,
            null,
            null,
        );
    }

    public static function restore(
        string $id,
        string $title,
        string $author,
        Isbn $isbn,
        string $categoryId,
        Quantity $totalCopies,
        Quantity $availableCopies,
        ?string $coverKey,
        ?\DateTimeImmutable $deletedAt,
    ): self {
        return new self(
            $id,
            $title,
            $author,
            $isbn,
            $categoryId,
            $totalCopies,
            $availableCopies,
            $coverKey,
            $deletedAt,
        );
    }

    /**
     * CA-06.3 · One copy leaves the building.
     *
     * The domain refuses to go below zero. This is the in-code half of the
     * guarantee; `ck_book_available_range` in the engine is the last barrier.
     */
    public function withdraw(): void
    {
        if (0 === $this->availableCopies->value) {
            throw new BookNotAvailable($this->title);
        }

        $this->availableCopies = $this->availableCopies->minusOne();
    }

    /**
     * CA-07.1 / CA-07.3 · One copy comes back.
     *
     * Restocking above the total is refused here. The domain does not trust
     * that it is only ever called once per returned line.
     */
    public function restock(): void
    {
        if ($this->availableCopies->value >= $this->totalCopies->value) {
            throw new InvalidQuantity($this->availableCopies->value + 1);
        }

        $this->availableCopies = $this->availableCopies->plusOne();
    }

    /**
     * CA-03.3 · Changing the total adjusts the available copies by the same
     * difference, and never drives the available copies below zero.
     *
     * The check is `remaining < 0`, not `remaining === 0`: zero available
     * copies is legal, and the distinction is a real 422-vs-200 case.
     */
    public function changeTotalCopies(int $newTotal): void
    {
        $lend = $this->availableCopies->value;

        if ($newTotal < $lend) {
            throw new TotalCopiesBelowLent($newTotal, $lend);
        }

        $this->totalCopies = Quantity::of($newTotal);
        $this->availableCopies = Quantity::of($newTotal - $lend);
    }

    /**
     * CA-03.5 · Soft delete (ADR-003).
     *
     * $openLines is supplied by the caller because counting open loan lines is
     * a query, and the domain does not query anything.
     */
    public function softDelete(int $openLines, \DateTimeImmutable $now): void
    {
        if ($openLines > 0) {
            throw new BookHasOpenLoans($this->title);
        }

        $this->deletedAt = $now;
    }

    public function withCoverKey(?string $coverKey): void
    {
        // CA-03.4: no cover means null, never an empty string.
        $this->coverKey = ('' === $coverKey) ? null : $coverKey;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function author(): string
    {
        return $this->author;
    }

    public function isbn(): Isbn
    {
        return $this->isbn;
    }

    public function categoryId(): string
    {
        return $this->categoryId;
    }

    public function totalCopies(): Quantity
    {
        return $this->totalCopies;
    }

    public function availableCopies(): Quantity
    {
        return $this->availableCopies;
    }

    public function coverKey(): ?string
    {
        return $this->coverKey;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }
}