<?php

declare(strict_types=1);

namespace App\Adapters\Http\Error;

use App\Domain\Shared\DomainException;

/**
 * The single error body of api-contract.md §1, applied to one business rule.
 *
 * Every error the API can return is declared here once. Nothing else in the
 * HTTP layer is allowed to invent a status code: an endpoint that needs a new
 * one adds a case here first.
 *
 * `code`      the machine-readable code from api-contract.md §2
 * `status`    the HTTP status from api-contract.md §1
 * `message`   the human-readable sentence, in English (Article XI)
 */
enum BusinessError: string
{
    // 401 — CA-01.2, CA-01.3
    case InvalidCredentials = 'INVALID_CREDENTIALS';
    case Unauthenticated = 'UNAUTHENTICATED';
    case TokenExpired = 'TOKEN_EXPIRED';
    case TokenInvalid = 'TOKEN_INVALID';

    // 403 — CA-01.3
    case ForbiddenRole = 'FORBIDDEN_ROLE';

    // 404 — CA-04.3 and any other missing resource
    case NotFound = 'NOT_FOUND';

    // 409 — duplicate, no copies, already returned, member with overdue, book with open loans
    case UsernameTaken = 'USERNAME_TAKEN';
    case IsbnTaken = 'ISBN_TAKEN';
    case DocumentTaken = 'DOCUMENT_TAKEN';
    case BookHasOpenLoans = 'BOOK_HAS_OPEN_LOANS';
    case BookNotAvailable = 'BOOK_NOT_AVAILABLE';
    case MemberHasOverdue = 'MEMBER_HAS_OVERDUE';
    case ItemAlreadyReturned = 'ITEM_ALREADY_RETURNED';

    // 422 — validation
    case MemberLimitExceeded = 'MEMBER_LIMIT_EXCEEDED';
    case DuplicateBookInLoan = 'DUPLICATE_BOOK_IN_LOAN';
    case ValidationError = 'VALIDATION_ERROR';
    case InvalidDateRange = 'INVALID_DATE_RANGE';

    public function status(): int
    {
        return match ($this) {
            self::InvalidCredentials,
            self::Unauthenticated,
            self::TokenExpired,
            self::TokenInvalid => 401,

            self::ForbiddenRole => 403,

            self::NotFound => 404,

            self::UsernameTaken,
            self::IsbnTaken,
            self::DocumentTaken,
            self::BookHasOpenLoans,
            self::BookNotAvailable,
            self::MemberHasOverdue,
            self::ItemAlreadyReturned => 409,

            self::MemberLimitExceeded,
            self::DuplicateBookInLoan,
            self::ValidationError,
            self::InvalidDateRange => 422,
        };
    }

    /**
     * A domain exception thrown by the core is translated HERE and nowhere
     * else (architecture.md §5). A framework exception never reaches the
     * client.
     */
    public static function fromDomainException(DomainException $e): self
    {
        return match (true) {
            $e instanceof \App\Domain\Book\BookNotAvailable => self::BookNotAvailable,
            $e instanceof \App\Domain\Book\BookHasOpenLoans => self::BookHasOpenLoans,
            $e instanceof \App\Domain\Loan\ItemAlreadyReturned => self::ItemAlreadyReturned,
            $e instanceof \App\Domain\Loan\DuplicateBookInLoan => self::DuplicateBookInLoan,
            $e instanceof \App\Domain\Loan\InvalidLineCount => self::ValidationError,
            $e instanceof \App\Domain\Member\MemberHasOverdue => self::MemberHasOverdue,
            $e instanceof \App\Domain\Member\MemberLimitExceeded => self::MemberLimitExceeded,

            // Anything else is an invalid field, never a leak.
            default => self::ValidationError,
        };
    }

    /** @param list<array{field: string, issue: string}> $details */
    public function toBody(array $details = []): array
    {
        return [
            'code' => $this->value,
            'message' => $this->defaultMessage(),
            'details' => $details,
        ];
    }

    private function defaultMessage(): string
    {
        return match ($this) {
            self::InvalidCredentials => 'The credentials are not valid.',
            self::Unauthenticated => 'A bearer token is required.',
            self::TokenExpired => 'The bearer token has expired.',
            self::TokenInvalid => 'The bearer token is not valid.',
            self::ForbiddenRole => 'Your role does not allow this operation.',
            self::NotFound => 'The requested resource does not exist.',
            self::UsernameTaken => 'That username is already taken.',
            self::IsbnTaken => 'That ISBN is already registered on an active book.',
            self::DocumentTaken => 'That document number is already registered.',
            self::BookHasOpenLoans => 'The book has open loans and cannot be deleted.',
            self::BookNotAvailable => 'No available copies left.',
            self::MemberHasOverdue => 'The member has overdue loans.',
            self::ItemAlreadyReturned => 'That loan line was already returned.',
            self::MemberLimitExceeded => 'The member already has the maximum number of books on loan.',
            self::DuplicateBookInLoan => 'The same book appears twice in the same loan.',
            self::ValidationError => 'One or more fields are not valid.',
            self::InvalidDateRange => 'The end date must not be before the start date.',
        };
    }
}