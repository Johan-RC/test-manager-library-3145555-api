<?php

declare(strict_types=1);

namespace App\Adapters\Http\Security;

use App\Adapters\Http\Error\BusinessError;
use App\Adapters\Http\Error\ErrorHandler;
use Closure;
use Illuminate\Http\Request;

/**
 * CA-01.3 · Authorisation, evaluated per endpoint by role.
 *
 *   - No token, or a bad one: 401
 *   - Token valid but the role is insufficient: 403
 *   - A 403 never reveals whether the resource exists
 *
 * The role travels in the token, never in the request body, so a client cannot
 * escalate by sending `role: admin` (CA-02.3).
 */
final class RoleGuard
{
    public function __construct(private readonly ErrorHandler $errors)
    {
    }

    /** Any authenticated role. */
    public function authenticated(Request $request, Closure $next): mixed
    {
        $claims = $this->claims($request);

        return $next($request);
    }

    /** CA-05.1, CA-06.x, CA-07.x, CA-09.2 — admin and librarian alike. */
    public function staff(Request $request, Closure $next): mixed
    {
        return $this->anyOf(['admin', 'librarian'])($request, $next);
    }

    public function admin(Request $request, Closure $next): mixed
    {
        return $this->anyOf(['admin'])($request, $next);
    }

    /** @param list<string> $allowed */
    public function anyOf(array $allowed): Closure
    {
        return function (Request $request, Closure $next) use ($allowed): mixed {
            $claims = $this->claims($request);

            if (!in_array($claims['role'], $allowed, true)) {
                return $this->errors->respond(BusinessError::ForbiddenRole);
            }

            return $next($request);
        };
    }

    /**
     * @return array{sub: string, role: string, exp: int}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
     */
    private function claims(Request $request): array
    {
        /** @var TokenParser $parser */
        $parser = app(TokenParser::class);

        $token = $request->bearerToken();

        if (null === $token || '' === $token) {
            abort(401, 'Missing bearer token');
        }

        return $parser->parse($token);
    }
}