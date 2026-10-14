<?php

declare(strict_types=1);

namespace App\Adapters\Http\Security;

/**
 * The claims the API puts in the bearer token.
 *
 * api-contract.md §4: signed JWT, 8 hours, carrying `sub` and `role` only,
 * with no personal data inside.
 */
final readonly class TokenClaims
{
    public function __construct(
        public string $subject,
        public string $role,
        public int $expiresAt,
    ) {
    }

    /** @return array{sub: string, role: string, exp: int} */
    public function toArray(): array
    {
        return [
            'sub' => $this->subject,
            'role' => $this->role,
            'exp' => $this->expiresAt,
        ];
    }
}