<?php

declare(strict_types=1);

namespace App\Adapters\Http\Presenter;

use App\Domain\Loan\Loan;
use App\Domain\Loan\LoanItem;
use App\Domain\Shared\Clock;

/**
 * Turns a domain aggregate into the JSON the contract declares.
 *
 * Two rules are visible here:
 *   - `status` is COMPUTED from the clock, never read from a column (Art. VII)
 *   - `book_title`, `isbn` and `category_name` come from the FROZEN line, so
 *     renaming a book never rewrites a past loan (Art. VIII, ADR-004)
 *
 * api-contract.md §3.6.
 */
final class LoanPresenter
{
    public function __construct(private readonly Clock $clock)
    {
    }

    public function detail(Loan $loan): array
    {
        return [
            'id' => $loan->id(),
            'member_id' => $loan->memberId(),
            'loaned_by_user_id' => $loan->loanedByUserId(),
            'loaned_at' => $this->instant($loan->loanedAt()),
            'status' => $loan->state($this->clock),
            'items' => array_map(
                fn (LoanItem $item): array => $this->item($item),
                $loan->items(),
            ),
        ];
    }

    public function item(LoanItem $item): array
    {
        return [
            'id' => $item->id(),
            'book_id' => $item->bookId(),
            'book_title' => $item->bookTitle(),
            'isbn' => $item->isbn()->value,
            'category_name' => $item->categoryName(),
            'due_at' => $this->instant($item->dueAt()),
            'returned_at' => null === $item->returnedAt() ? null : $this->instant($item->returnedAt()),
        ];
    }

    /** ISO 8601 in UTC with a `Z` suffix, as §1 requires. */
    private function instant(\DateTimeImmutable $value): string
    {
        return $value->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}