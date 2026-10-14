<?php

declare(strict_types=1);

namespace App\Domain\Member;

use App\Domain\Shared\DomainException;

/** CA-06.5 / RN-3 · The member has overdue books. Maps to 409 MEMBER_HAS_OVERDUE. */
final class MemberHasOverdue extends DomainException
{
    public function __construct(public readonly int $overdueLines)
    {
        parent::__construct(sprintf(
            'The member has %d overdue line(s); overdue books must be returned before borrowing again.',
            $overdueLines,
        ));
    }
}