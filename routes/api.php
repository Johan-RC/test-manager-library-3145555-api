<?php

declare(strict_types=1);

use App\Adapters\Http\Security\RoleGuard;
use Illuminate\Support\Facades\Route;

/*
 * The routes of api-contract.md §3.
 *
 * Order matters and is not cosmetic: `/loans/overdue` is declared BEFORE
 * `/loans/{id}` on purpose. With the opposite order a real router captures the
 * literal `overdue` as the `{id}` parameter, the UUID parse fails, and CA-09.1
 * and CA-09.2 break.
 *
 * Roles per §3: admin, librarian, or public.
 */

Route::prefix('v1')->group(function (): void {

    // §3.1 — the only two public routes (CA-01.3).
    Route::get('/health', fn () => response()->json(['status' => 'ok']));

    Route::post('/auth/login', [App\Adapters\Http\Controller\AuthController::class, 'login']);

    // §3.3 — the five categories, any authenticated role.
    Route::get('/categories', [App\Adapters\Http\Controller\CategoryController::class, 'index'])
        ->middleware(RoleGuard::class.'@authenticated');

    // §3.4 — books.
    Route::get('/books', [App\Adapters\Http\Controller\BookController::class, 'index'])
        ->middleware(RoleGuard::class.'@authenticated');
    Route::get('/books/{book}/cover', [App\Adapters\Http\Controller\BookController::class, 'cover'])
        ->middleware(RoleGuard::class.'@authenticated');
    Route::get('/books/{book}', [App\Adapters\Http\Controller\BookController::class, 'show'])
        ->middleware(RoleGuard::class.'@authenticated');

    Route::post('/books', [App\Adapters\Http\Controller\BookController::class, 'store'])
        ->middleware(RoleGuard::class.'@admin');
    Route::put('/books/{book}', [App\Adapters\Http\Controller\BookController::class, 'update'])
        ->middleware(RoleGuard::class.'@admin');
    Route::delete('/books/{book}', [App\Adapters\Http\Controller\BookController::class, 'destroy'])
        ->middleware(RoleGuard::class.'@admin');
    Route::put('/books/{book}/cover', [App\Adapters\Http\Controller\BookController::class, 'uploadCover'])
        ->middleware(RoleGuard::class.'@admin');
    Route::delete('/books/{book}/cover', [App\Adapters\Http\Controller\BookController::class, 'removeCover'])
        ->middleware(RoleGuard::class.'@admin');

    // §3.5 — members: admin and librarian.
    Route::get('/members', [App\Adapters\Http\Controller\MemberController::class, 'index'])
        ->middleware(RoleGuard::class.'@staff');
    Route::get('/members/{member}', [App\Adapters\Http\Controller\MemberController::class, 'show'])
        ->middleware(RoleGuard::class.'@staff');
    Route::post('/members', [App\Adapters\Http\Controller\MemberController::class, 'store'])
        ->middleware(RoleGuard::class.'@staff');

    // §3.6 — loans. LITERAL ROUTES FIRST, see the note at the top.
    Route::get('/loans/overdue', [App\Adapters\Http\Controller\LoanController::class, 'overdue'])
        ->middleware(RoleGuard::class.'@staff');
    Route::get('/loans', [App\Adapters\Http\Controller\LoanController::class, 'index'])
        ->middleware(RoleGuard::class.'@staff');
    Route::get('/loans/{loan}', [App\Adapters\Http\Controller\LoanController::class, 'show'])
        ->middleware(RoleGuard::class.'@staff');
    Route::post('/loans', [App\Adapters\Http\Controller\LoanController::class, 'store'])
        ->middleware(RoleGuard::class.'@staff');
    Route::post('/loans/{loan}/items/{item}/return', [App\Adapters\Http\Controller\LoanController::class, 'returnItem'])
        ->middleware(RoleGuard::class.'@staff');

    // §3.7 — the report, admin only (CA-10.5).
    Route::get('/reports/most-borrowed', [App\Adapters\Http\Controller\ReportController::class, 'mostBorrowed'])
        ->middleware(RoleGuard::class.'@admin');

    // §3.2 — creating a librarian, admin only (CA-02.x).
    Route::post('/users', [App\Adapters\Http\Controller\UserController::class, 'store'])
        ->middleware(RoleGuard::class.'@admin');
});