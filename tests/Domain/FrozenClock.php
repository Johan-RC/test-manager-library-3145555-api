<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Shared\Clock;

/**
 * T-05 · A fake clock.
 *
 * D-05: the clock is a port so due dates and overdue states can be tested
 * without waiting 14 days. This is the test-side implementation; the
 * production one lives in the security/time adapter.
 */
final class FrozenClock implements Clock
{
    public function __construct(private \DateTimeImmutable $now)
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $modifier): void
    {
        $this->now = $this->now->modify($modifier);
    }

    public function set(string $iso): void
    {
        $this->now = new \DateTimeImmutable($iso);
    }
}