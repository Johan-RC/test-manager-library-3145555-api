<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * T-02 · Initial migration: six tables, foreign keys, unique indexes and the
 * five seeded categories.
 *
 * Everything here comes from data-model.md. Two rules drive the file:
 *
 *   1. NO COLUMN CARRIES A DEFAULT (data-model.md §3). Laravel adds
 *      `id`, `created_at` and `updated_at` by default, so every one of
 *      those is opted out of explicitly.
 *   2. NO BUSINESS TABLES OUTSIDE THE `library` SCHEMA. Laravel's own
 *      tables (sessions, cache, jobs) go to `public`, untouched by the
 *      business model.
 *
 * `ck_book_total_positive` and `ck_user_role` are NOT created here: they
 * are marked "pending (T-18)" in data-model.md §4 and belong to T-18.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS library');

        $this->createCategory();
        $this->createUser();
        $this->createBook();
        $this->createMember();
        $this->createLoan();
        $this->createLoanItem();
        $this->seedCategories();
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS library CASCADE');
    }

    /**
     * category — read-only seed of the five fixed categories (data-model.md §9).
     * Identifiers are literal: the test data and the seeder rely on them.
     */
    private function createCategory(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE library.category (
                id   uuid         NOT NULL,
                name varchar(120) NOT NULL,
                CONSTRAINT pk_category PRIMARY KEY (id),
                CONSTRAINT uq_category_name UNIQUE (name)
            )
        SQL);
    }

    private function createUser(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE library."user" (
                id            uuid         NOT NULL,
                username      varchar(120) NOT NULL,
                password_hash varchar(512) NOT NULL,
                role          varchar(40)  NOT NULL,
                CONSTRAINT pk_user PRIMARY KEY (id),
                CONSTRAINT uq_user_username UNIQUE (username)
            )
        SQL);
    }

    /**
     * book — Art. VII forbids a stored state, so there is no `status` column
     * here and none is ever added.
     */
    private function createBook(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE library.book (
                id               uuid          NOT NULL,
                title            varchar(250)  NOT NULL,
                author           varchar(200)  NOT NULL,
                isbn             char(13)      NOT NULL,
                category_id      uuid          NOT NULL,
                total_copies     integer       NOT NULL,
                available_copies integer       NOT NULL,
                cover_key        varchar(512)  NULL,
                deleted_at       timestamptz   NULL,
                CONSTRAINT pk_book PRIMARY KEY (id),
                CONSTRAINT ck_book_available_range
                    CHECK (available_copies >= 0 AND available_copies <= total_copies),
                CONSTRAINT fk_book_category
                    FOREIGN KEY (category_id) REFERENCES library.category (id)
                    ON DELETE RESTRICT ON UPDATE NO ACTION
            )
        SQL);

        // ISBN is unique only among active books (ADR-003), so a deleted
        // ISBN can be registered again.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_book_isbn_active
                ON library.book (isbn) WHERE deleted_at IS NULL
        SQL);

        // Access index for Q1: category filter, ordered by title, active only.
        DB::statement(<<<'SQL'
            CREATE INDEX idx_book_category_title_active
                ON library.book (category_id, title) WHERE deleted_at IS NULL
        SQL);
    }

    private function createMember(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE library.member (
                id              uuid         NOT NULL,
                full_name       varchar(200) NOT NULL,
                document_number varchar(30)  NOT NULL,
                CONSTRAINT pk_member PRIMARY KEY (id),
                CONSTRAINT uq_member_document_number UNIQUE (document_number)
            )
        SQL);
    }

    /**
     * loan — immutable except for loan_item.returned_at (RN-5). There are no
     * audit columns; the business instants are the columns (data-model.md §8).
     */
    private function createLoan(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE library.loan (
                id                uuid        NOT NULL,
                member_id         uuid        NOT NULL,
                loaned_by_user_id uuid        NOT NULL,
                loaned_at         timestamptz NOT NULL,
                CONSTRAINT pk_loan PRIMARY KEY (id),
                CONSTRAINT fk_loan_member
                    FOREIGN KEY (member_id) REFERENCES library.member (id)
                    ON DELETE RESTRICT ON UPDATE NO ACTION,
                CONSTRAINT fk_loan_loaned_by_user
                    FOREIGN KEY (loaned_by_user_id) REFERENCES library."user" (id)
                    ON DELETE RESTRICT ON UPDATE NO ACTION
            )
        SQL);

        DB::statement('CREATE INDEX idx_loan_loaned_at ON library.loan (loaned_at)');
    }

    /**
     * loan_item — the frozen snapshot lives here (Art. VIII, ADR-004).
     * `category_name` deliberately has NO foreign key, and `book_title`,
     * `isbn` and `category_name` deliberately have no defaults: the domain
     * writes the values that were read at checkout time.
     */
    private function createLoanItem(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE library.loan_item (
                id            uuid        NOT NULL,
                loan_id       uuid        NOT NULL,
                book_id       uuid        NOT NULL,
                book_title    varchar(250) NOT NULL,
                isbn          char(13)    NOT NULL,
                category_name varchar(120) NOT NULL,
                due_at        timestamptz NOT NULL,
                returned_at   timestamptz NULL,
                CONSTRAINT pk_loan_item PRIMARY KEY (id),
                CONSTRAINT uq_loan_item_loan_book UNIQUE (loan_id, book_id),
                CONSTRAINT fk_loan_item_loan
                    FOREIGN KEY (loan_id) REFERENCES library.loan (id)
                    ON DELETE CASCADE ON UPDATE NO ACTION,
                CONSTRAINT fk_loan_item_book
                    FOREIGN KEY (book_id) REFERENCES library.book (id)
                    ON DELETE RESTRICT ON UPDATE NO ACTION
            )
        SQL);

        DB::statement('CREATE INDEX idx_loan_item_book_id ON library.loan_item (book_id)');

        // Q8 / HU-09: the overdue listing reads exactly this.
        DB::statement(<<<'SQL'
            CREATE INDEX idx_loan_item_due_at_open
                ON library.loan_item (due_at) WHERE returned_at IS NULL
        SQL);
    }

    /**
     * The five categories, with their literal identifiers (data-model.md §9).
     * Names are data, not prose: they are stored values and are not translated.
     */
    private function seedCategories(): void
    {
        $categories = [
            ['11111111-1111-4111-8111-111111111111', 'Novela'],
            ['22222222-2222-4222-8222-222222222222', 'Ciencia'],
            ['33333333-3333-4333-8333-333333333333', 'Tecnología'],
            ['44444444-4444-4444-8444-444444444444', 'Historia'],
            ['55555555-5555-4555-8555-555555555555', 'Infantil'],
        ];

        foreach ($categories as [$id, $name]) {
            DB::select(
                'INSERT INTO library.category (id, name) VALUES (?, ?) ON CONFLICT (id) DO NOTHING',
                [$id, $name]
            );
        }
    }
};