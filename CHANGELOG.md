# Changelog

All notable changes to `fb-copydb` will be documented in this file.

## Unreleased

### Changed

- Bump Guzzle to 7.15.2 to fix the Proxy-Authorization CVE (GHSA-94pj-82f3-465w).
- Bring the package up to the fb-* standard: CI six gates (validate, audit, pint, phpstan, pest), `pint.json`, `phpstan.neon.dist`, CONTRIBUTING and SECURITY policies, and a rewritten README.
- Remove the unreachable PostgreSQL block in `createDestinationDatabase()`; PostgreSQL destinations remain unsupported.
- Remove the unused `--old_filament_base` option.
- Use the `db` argument for the SQLite database file in `makeConnection()`.
- Guard the empty-source-database case when resolving table names.
- Clean up `ignoreTables()` output when `--tables_except` is not provided.

## v4.2.2 - 2026-06-03

### Changed

- General maintenance update.

## v4.2.1 - 2025-12-22

### Added

- `--no-migrate` option to skip dropping and migrating the destination database.

## v4.2.0 - 2025-12-22

### Added

- Drop the destination database before running migrations.

## v4.1.3 - 2025-12-09

### Fixed

- Minor test adjustments.

## v4.1.2 - 2025-12-09

### Fixed

- Minor test adjustments.

## v4.1.1 - 2025-10-19

### Changed

- Update the CI workflow.

## v4.1.0 - 2025-10-14

### Added

- Initial test coverage for the copy command.

## v4.0.7 - 2025-09-03

### Added

- `--tables_except` option to skip copying specific tables.

## v4.0.6 - 2025-08-29

### Changed

- Add message tables to the ignore list because their structure changed.

## v4.0.5 - 2025-08-24

### Changed

- Update composer.json and use Pest 4.0; update tests.

## v4.0.4 - 2025-08-24

### Changed

- Update composer.json and use Pest 4.0; update tests.

## v4.0.3 - 2025-08-16

### Fixed

- Resolve several issues when migrating from an old database.

## v4.0.2 - 2025-08-12

### Fixed

- Replace `expired_at` with `expiration_date` when converting user data.

## v4.0.1 - 2025-08-11

### Fixed

- Various refinements.

## v4.0.0 - 2025-08-09

### Changed

- Bump to v4 (breaking).

## v3.0.0 - 2025-08-09

### Changed

- Bump to v3 (breaking).

## v2.0.0 - 2025-08-09

### Changed

- Bump to v2 (breaking).

## v1.0.0 - 2025-08-09

### Added

- Initial release.
