<?php

declare(strict_types=1);

namespace App\Domain\Member;

use App\Domain\Shared\DomainException;

/**
 * CA-06.4 · The member already holds the maximum of unreturned books.
 * Maps to 422 MEMBER_LIMIT_EXCEEDED and carries the current count, as CA-06.4
 * requires.
 */
final class MemberLimitExceeded extends DomainException
{
    public function __construct(
        public readonly int $current,
        public readonly int $max,
    ) {
        parent::__construct(sprintf(
            'The member already has %d books not returned; the maximum is %d.',
            $current,
            $max,
        ));
    }
}