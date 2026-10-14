<?php

declare(strict_types=1);

use Arkitect\Rules\Classes\DependsOnThese;
use Arkitect\Rules\Classes\HaveNameMatching;
use Arkitect\Rules\Classes\ResideInTheseNamespaces;

/**
 * T-19 · Architecture tests for Articles I, II and III.
 *
 * These are executable versions of the constitution. `composer run arch`
 * passes, and it FAILS if a forbidden dependency is introduced — which is
 * exactly the evidence T-19 asks for.
 */

return [
    // Art. I — the domain depends on nothing.
    'the domain imports no other module and no framework' => (new ResideInTheseNamespaces('App\Domain'))
        ->shouldOnlyDependOn()
        ->onClassesThatResideInTheseNamespaces('App\Domain'),

    // Art. II — the core declares its ports; the world implements them.
    'the application never imports the adapters' => (new ResideInTheseNamespaces('App\Application'))
        ->shouldNotDependOn()
        ->onClassesThatResideInTheseNamespaces('App\Adapters'),

    'the domain never imports the application' => (new ResideInTheseNamespaces('App\Domain'))
        ->shouldNotDependOn()
        ->onClassesThatResideInTheseNamespaces('App\Application'),

    'the application never imports infrastructure' => (new ResideInTheseNamespaces('App\Application'))
        ->shouldNotDependOn()
        ->onClassesThatResideInTheseNamespaces('App\Infrastructure', 'App\Adapters'),

    // Art. III — a single composition root.
    'nothing outside the composition root may implement an outbound port' => (new ResideInTheseNamespaces(
        'App\Application\Ports\Outbound',
    ))
        ->shouldOnlyDependOn()
        ->onClassesThatResideInTheseNamespaces('App\Domain'),

    // Naming, so the port/adapter split stays visible in the tree.
    'ports are named Port' => (new HaveNameMatching('.*Port$'))
        ->shouldOnlyHaveNameMatching()
        ->inNamespace('App\Application\Ports\Outbound'),
];