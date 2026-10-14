<?php

declare(strict_types=1);

namespace App\Domain\Loan;

use App\Domain\Book\Book;
use App\Domain\Shared\Clock;

/**
 * T-05 · Loan — an aggregate root.
 *
 * Invariants it defends (architecture.md §3):
 *   - 1 to 3 lines
 *   - no repeated book within the loan
 *   - due date is exactly loaned_at + 14 days (RN-2)
 *   - immutable except for returning a line (RN-5)
 *   - `open` / `overdue` / `closed` are COMPUTED, never stored (Art. VII)
 *
 * Loan.AddItem calls Book.withdraw() before adding the line, and
 * Loan.returnItem() calls Book.restock() exactly once: subtracting the copy
 * and creating the line are one single operation (CA-06.3).
 */
final class Loan
{
    public const MAX_LINES = 3;
    public const LOAN_PERIOD_DAYS = 14;

    /** @var list<LoanItem> */
    private array $items = [];

    private function __construct(
        private readonly string $id,
        private readonly string $memberId,
        private readonly string $loanedByUserId,
        private readonly \DateTimeImmutable $loanedAt,
    ) {
    }

    public static function start(
        string $id,
        string $memberId,
        string $loanedByUserId,
        Clock $clock,
    ): self {
        return new self($id, $memberId, $loanedByUserId, $clock->now());
    }

    public static function restore(
        string $id,
        string $memberId,
        string $loanedByUserId,
        \DateTimeImmutable $loanedAt,
        array $items,
    ): self {
        $loan = new self($id, $memberId, $loanedByUserId, $loanedAt);

        foreach ($items as $item) {
            $loan->items[] = $item;
        }

        return $loan;
    }

    /**
     * CA-06.2 / CA-06.3 / CA-06.6 · Add one line.
     *
     * The frozen values are read from the live catalogue HERE, at this instant,
     * and never again (Art. VIII).
     */
    public function addItem(
        string $itemId,
        Book $book,
        string $categoryName,
        Clock $clock,
    ): LoanItem {
        if (count($this->items) >= self::MAX_LINES) {
            throw new InvalidLineCount(count($this->items) + 1);
        }

        foreach ($this->items as $existing) {
            if ($existing->bookId() === $book->id()) {
                throw new DuplicateBookInLoan($book->title());
            }
        }

        // One operation: the copy leaves and the line is created.
        $book->withdraw();

        $item = LoanItem::open(
            $itemId,
            $this->id,
            $book->id(),
            $book->title(),
            $book->isbn(),
            $categoryName,
            $this->loanedAt->modify(sprintf('+%d days', self::LOAN_PERIOD_DAYS)),
        );

        $this->items[] = $item;

        return $item;
    }

    /**
     * CA-07.1 / CA-07.2 / CA-07.4 · Return one line.
     *
     * restock() is called exactly once, because markReturned() refuses a line
     * that already carries a returned_at.
     */
    public function returnItem(string $itemId, Book $book, Clock $clock): LoanItem
    {
        $item = $this->itemById($itemId);

        $item->markReturned($clock->now());
        $book->restock();

        return $item;
    }

    /**
     * Art. VII · The state is derived. There is no `status` column and none
     * is ever added; this method is the only source of it.
     */
    public function state(Clock $clock): string
    {
        if ([] === $this->items) {
            return 'closed';
        }

        $now = $clock->now();

        foreach ($this->items as $item) {
            if (!$item->isReturned() && $now > $item->dueAt()) {
                return 'overdue';
            }
        }

        foreach ($this->items as $item) {
            if (!$item->isReturned()) {
                return 'open';
            }
        }

        return 'closed';
    }

    public function itemById(string $itemId): LoanItem
    {
        foreach ($this->items as $item) {
            if ($item->id() === $itemId) {
                return $item;
            }
        }

        throw new LoanItemNotFound($itemId);
    }

    /** @return list<LoanItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function memberId(): string
    {
        return $this->memberId;
    }

    public function loanedByUserId(): string
    {
        return $this->loanedByUserId;
    }

    public function loanedAt(): \DateTimeImmutable
    {
        return $this->loanedAt;
    }
}