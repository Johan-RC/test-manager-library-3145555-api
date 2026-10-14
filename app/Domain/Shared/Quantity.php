<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * T-03 · Quantity — a value object.
 *
 * Invariant (T-03 evidence, CA-03.3): strictly greater than zero.
 */
final readonly class Quantity
{
    private function __construct(
        public int $value,
    ) {
    }

    public static function of(int $value): self
    {
        if ($value < 1) {
            throw new InvalidQuantity($value);
        }

        return new self($value);
    }

    /** The copy counter reaching zero is legal; that is what "0 available" means. */
    public static function zero(): self
    {
        return new self(0);
    }

    public function minusOne(): self
    {
        return new self($this->value - 1);
    }

    public function plusOne(): self
    {
        return new self($this->value + 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}