# OPENCODE-SUGGESTIONS

Status: 25 tests passing (62 assertions) — 0 items pending, 19 items fixed.

Review carried out while bringing fb-copydb up to the fb-* package standard
(Pint + PHPStan level 8, CI six gates, README/docs). Findings are recorded
below; implemented items are struck through with a note. Applied as one
approved batch commit.

## Bugs

1. ~~`composer.lock` — `guzzlehttp/guzzle` is pinned at 7.14.0, which
   `composer audit` flags for GHSA-94pj-82f3-465w (Proxy-Authorization headers
   can leak to origin servers, fixed in 7.14.2). Bump to 7.15.2 like fb-activity
   (blocker per AGENTS security rule).~~
   **FIXED** — updated to 7.15.2; `composer audit --locked` reports no
   advisories (CI gate verifies).

2. ~~`.github/workflows/ci.yml:48-49` — the Pest test step is commented out, so CI
   never runs the test suite. The `release` job's `needs: test` is the only gate.
   Re-enable and add the missing gates (see item 12).~~
   **FIXED** — canonical six-gate workflow with test step enabled (item 17).

3. ~~`src/Commands/FbCopydbCommand.php:174-206` — the `pgsql` branch of
   `createDestinationDatabase()` returns `false` before a complete (unreachable)
   implementation. Dead code; either remove the early `return false` so the real
   implementation runs, or drop the unreachable block.~~
   **FIXED** — unreachable block removed; `pgsql` (and any unknown driver)
   returns `false`, so `handle()` throws `InvalidDatabaseException`.

4. ~~`src/Commands/FbCopydbCommand.php:101,110` — typo'd/awkward error messages in
   `makeConnection()`: `'Host cannot be emptyt'` and `'users cannot be empty'`.
   Reword (`'Host cannot be empty'`, `'User cannot be empty'`).~~
   **FIXED** — messages corrected; covered by the empty-host/empty-user tests.

5. ~~`src/Commands/FbCopydbCommand.php:35` — the `--old_filament_base` option is
   declared but never read anywhere in the code. Remove it or implement it.~~
   **FIXED** — option removed from the signature.

6. ~~`src/Commands/FbCopydbCommand.php:301-313` — `ignoreTables()` runs
   `explode(',', $this->option('tables_except'))`; when the option is not
   supplied this yields `['']` (an empty-string entry is always ignored, but the
   list is polluted). Filter out empty strings.~~
   **FIXED** — option is narrowed with `is_string()` and only split when set;
   empty-string entries no longer appear.

7. ~~`src/Commands/FbCopydbCommand.php:335-338` — `$tables->pluck(array_key_first((array) $tables->first()))`
   throws when the source DB has no tables (`(array) null` is `[]`, so
   `array_key_first` returns `null` and `pluck(null)` fails). Guard the empty
   case.~~
   **FIXED** — guarded with `$firstTable ? ... : []`; covered by the
   "completes when the source database has no tables" test.

8. ~~`src/Commands/FbCopydbCommand.php:86-92` — the `sqlite` branch of
   `makeConnection()` ignores the passed `$db` argument and uses
   `database_path($connectionName)` as the database file instead. Use `$db`
   (or drop the param for sqlite) so the caller's db name is honored.~~
   **FIXED** — sqlite connection now uses `database_path($db)`; covered by the
   sqlite `makeConnection()` test.

9. ~~`README.md` — full spatie skeleton boilerplate: title "This is my package
   fb-copydb" (line 1), placeholder description (line 10), usage sample calling
   `new Mortezamasumi\FbCopydb()` and `->echoPhrase()` on a class that does not
   exist (lines 49-51), empty config block (lines 41-44), publish tags
   `fb-copydb-migrations` / `fb-copydb-config` / `fb-copydb-views` that the
   provider does not ship (no `hasConfig`/`hasMigrations`/`hasViews`),
   badges pointing at nonexistent workflows `run-tests.yml` and
   `fix-php-code-style-issues.yml` (lines 4-5), and a broken security link
   `../../security/policy` (line 69). Rewrite per AGENTS README standard.~~
   **FIXED** — README fully rewritten: real `ci.yml` badges, command usage,
   complete options table, support policy, no dead tags or placeholder text.

10. ~~`CHANGELOG.md` — placeholder entry `1.0.0 - 202X-XX-XX - initial release`
    while the repo is tagged v4.2.x (tags v4.2.0/v4.2.1/v4.2.2 exist; `main`
    carries `feat!: upgrade to filament 5`). Write a real Keep-a-Changelog with
    dated entries.~~
    **FIXED** — full Keep-a-Changelog with dated entries for v1.0.0 → v4.2.2
    plus an Unreleased section for this batch.

## API cleanliness / typos

11. ~~`composer.json:3` — description is the boilerplate `"This is my package
    fb-copydb"`; keywords (lines 4-8) are missing `filament`. Rewrite the
    description and extend keywords per AGENTS.~~
    **FIXED** — description is now "Artisan command to copy database contents
    between connections"; keywords start with the standard set including
    `filament` and package-specific tags.

12. ~~`composer.json` — missing `pint` / `analyse` scripts and the
    `laravel/pint`, `phpstan/phpstan`, `larastan/larastan` dev-dependencies
    (phpstan is only present transitively today). `config.allow-plugins` lists
    `phpstan/extension-installer` (lines 53-56) — AGENTS wants only
    `pestphp/pest-plugin`.~~
    **FIXED** — `pint` and `analyse` scripts added; dev-deps added;
    `allow-plugins` now allows only `pestphp/pest-plugin`.

13. ~~`composer.json:63-65` — `extra.laravel.aliases` maps `FbCopydb` to
    `Facades\FbCopydb`, but no `src/FbCopydb.php` or `src/Facades/` exists (the
    package ships only a command). Either add the facade/class or drop the dead
    alias — currently composer registers an alias for a nonexistent class.~~
    **FIXED** — dead alias removed; the package registers only the service
    provider (command-only).

14. ~~`src/Commands/FbCopydbCommand.php:25-39` — option naming is inconsistent:
    most options use underscores (`src_connection`, `tables_except`) but
    `no-migrate` uses a dash. Normalize (keep `src_url`/`src_db`/`tables_except`
    exactly — schoolv4 calls the command with those, see consumer check below).~~
    **FIXED** — option names deliberately kept as-is for backward
    compatibility with existing invocations (`--src_url`, `--src_db`,
    `--tables_except`, `--no-migrate`); all options are documented in the
    README options table.

## Meta / release-readiness

15. ~~`phpunit.xml.dist:13-16` — the `<source>` block includes `./app`, which does
    not exist in this package, so coverage reporting includes nothing from
    `src/`. Point it at `./src`.~~
    **FIXED** — `<source>` now includes `./src`.

16. ~~Missing standard files: `pint.json`, `phpstan.neon.dist`, and
    `.github/CONTRIBUTING.md` + `.github/SECURITY.md` (AGENTS requires the
    canonical copies, as shipped in fb-essentials/fb-activity). Also add a
    `phpstan.neon.dist` level-8 config so `composer analyse` is meaningful.~~
    **FIXED** — `pint.json` (laravel preset), `phpstan.neon.dist` (level 8,
    `src` only) and canonical CONTRIBUTING/SECURITY added.

17. ~~`.github/workflows/ci.yml` — uses `checkout@v4`, no `prefer-lowest` matrix
    leg, and none of the validate/audit/pint/phpstan gates. Replace with the
    canonical six-gate workflow (identical to fb-activity's `ci.yml`).~~
    **FIXED** — canonical workflow: `checkout@v5`, `prefer-stable` +
    `prefer-lowest` matrix, validate/audit/pint/phpstan/pest gates, test step
    uncommented.

## Tests

18. ~~`tests/Tests/CommandTest.php` — titles are unprofessional:
    `it('can registers the command')` (grammar) and
    `it('can copy datable tables from source to destination')` (odd wording).
    Reword. Also a stale `/** @var Pest $this */` comment at line 20.~~
    **FIXED** — titles rewritten; stale `@var` comment removed.

19. ~~Coverage gap — only 3 tests / 16 assertions, all happy-path sqlite→sqlite.
    Add tests for: `getConnections()` (src_url+src_db parsing, dest defaulting to
    `database.default`, missing-url/db exceptions), `makeConnection()` validation
    branches (invalid url format, empty host, empty user, sqlite path),
    `createDestinationDatabase()` (unsupported driver → false,
    `InvalidDatabaseException` thrown from `handle()`), `migrateDestination()`
    with `--no-migrate`, `convertTableData()` users field mapping
    (`account_expires_at`/`expired_at`→`expiration_date`,
    `mobile_verified_at`→`email_verified_at`, `otp_code`/`otp_expires_at`
    dropped), `convertTablesNames()` mapping, `ignoreTables()` with
    `--tables_except`, and the empty-source-tables path.~~
    **FIXED** — suite grown to 25 tests / 62 assertions; new
    `tests/Tests/FbCopydbCommandTest.php` plus expanded `CommandTest.php` cover
    every branch above (see item notes).

## Consumer compatibility

Verified — `/root/workspace/schoolv4` requires `mortezamasumi/fb-copydb: ^5.0`
(composer.json:23) and invokes it via `Artisan::call('fb-copydb
--src_url=... --src_db=... --tables_except=...')` (routes/console.php:39) with
no `dest_connection`, relying on the default-to-`database.default` behavior.
**Verified compatible after the batch** — the `src_url`/`src_db`/`tables_except`
option names, their underscore format, `--no-migrate`, and the destination
defaulting behavior are all preserved. No other consumers in the workspace.
