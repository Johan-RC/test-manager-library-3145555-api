<?php

declare(strict_types=1);

namespace App\Adapters\Http\Error;

use App\Domain\Shared\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * RNF-4 · Every HTTP error uses the same body.
 *
 * One single place in the whole application builds an error response. A route
 * names a `BusinessError`; it never writes its own shape.
 *
 * RNF-3 / Article IX: the body never carries a stack trace, SQL, a server path
 * or a password. Anything that is not a recognised business failure becomes a
 * generic VALIDATION_ERROR and the real exception is logged, not returned.
 */
final class ErrorHandler
{
    /** @param list<array{field: string, issue: string}> $details */
    public function respond(BusinessError $error, array $details = [], ?int $status = null): JsonResponse
    {
        return new JsonResponse($error->toBody($details), $status ?? $error->status());
    }

    public function render(Request $request, \Throwable $e): JsonResponse
    {
        if ($e instanceof DomainException) {
            report($e);

            return $this->respond(BusinessError::fromDomainException($e));
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            $error = match ($status) {
                401 => BusinessError::Unauthenticated,
                403 => BusinessError::ForbiddenRole,
                404 => BusinessError::NotFound,
                default => BusinessError::ValidationError,
            };

            if ($status >= 500) {
                report($e);
            }

            return $this->respond($error, [], $status);
        }

        report($e);

        return $this->respond(BusinessError::ValidationError, [], 500);
    }
}