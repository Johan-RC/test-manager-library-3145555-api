<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * A business rule was broken.
 *
 * Art. V of the constitution: the domain throws its own errors. Only the HTTP
 * adapter translates them into the single body of api-contract.md §1.
 * A framework exception never reaches the client.
 */
abstract class DomainException extends \DomainException
{
}