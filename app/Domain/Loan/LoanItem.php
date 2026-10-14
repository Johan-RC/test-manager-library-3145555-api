<?php

declare(strict_types=1);

namespace App\Domain\Loan;

use App\Domain\Book\Isbn;

/**
 * T-05 · LoanItem — a line of a loan.
 *
 * Art. VIII / ADR-004: the title, the ISBN and the category name are frozen
 * at checkout and never read from the live catalogue again. That is why they
 * are values on this line and not a lookup.
 */
final class LoanItem
{
    private function __construct(
        private readonly string $id,
        private readonly string $loanId,
        private readonly string $bookId,
        private readonly string $bookTitle,
        private readonly Isbn $isbn,
        private readonly string $categoryName,
        private readonly \DateTimeImmutable $dueAt,
        private ?\DateTimeImmutable $returnedAt,
    ) {
    }

    public static function open(
        string $id,
        string $loanId,
        string $bookId,
        string $bookTitle,
        Isbn $isbn,
        string $categoryName,
        \DateTimeImmutable $dueAt,
    ): self {
        return new self($id, $loanId, $bookId, $bookTitle, $isbn, $categoryName, $dueAt, null);
    }

    public static function restore(
        string $id,
        string $loanId,
        string $bookId,
        string $bookTitle,
        Isbn $isbn,
        string $categoryName,
        \DateTimeImmutable $dueAt,
        ?\DateTimeImmutable $returnedAt,
    ): self {
        return new self($id, $loanId, $bookId, $bookTitle, $isbn, $categoryName, $dueAt, $returnedAt);
    }

    public function isReturned(): bool
    {
        return null !== $this->returnedAt;
    }

    /** CA-09.1 · Whole UTC days elapsed since the due date, never negative. */
    public function daysLate(\DateTimeImmutable $now): int
    {
        if ($this->isReturned() || $now <= $this->dueAt) {
            return 0;
        }

        return (int) $now->diff($this->dueAt)->format('%a');
    }

    public function id(): string
    {
        return $this->id;
    }

    public function loanId(): string
    {
        return $this->loanId;
    }

    public function bookId(): string
    {
        return $this->bookId;
    }

    public function bookTitle(): string
    {
        return $this->bookTitle;
    }

    public function isbn(): Isbn
    {
        return $this->isbn;
    }

    public function categoryName(): string
    {
        return $this->categoryName;
    }

    public function dueAt(): \DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function returnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    /** CA-07.1 · The only mutation this line ever gets. */
    public function markReturned(\DateTimeImmutable $now): void
    {
        if ($this->isReturned()) {
            throw new ItemAlreadyReturned($this->bookTitle);
        }

        $this->returnedAt = $now;
    }
}