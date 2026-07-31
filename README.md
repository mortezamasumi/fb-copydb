# FB CopyDB — Database Copy Command

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mortezamasumi/fb-copydb.svg?style=flat-square)](https://packagist.org/packages/mortezamasumi/fb-copydb)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mortezamasumi/fb-copydb/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/mortezamasumi/fb-copydb/actions?query=branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mortezamasumi/fb-copydb.svg?style=flat-square)](https://packagist.org/packages/mortezamasumi/fb-copydb)
[![License](https://img.shields.io/packagist/l/mortezamasumi/fb-copydb.svg?style=flat-square)](LICENSE.md)

A Laravel artisan command that copies the contents of a source database into a
destination database. It supports SQLite and MySQL connections, configurable
per-connection options, chunked inserts, and legacy `filament-base` user
column/table-name conversion.

---

## Features

- **Copy between connections** — use a connection defined in `database.php` or build one inline with `--src_url` / `--dest_url`
- **Automatic destination setup** — creates the destination database and runs migrations (unless `--no-migrate` is given)
- **Chunked inserts** — configurable `--chunk_size` (default 1000) to keep memory usage low
- **Legacy conversion** — maps old `filament-base` user columns (`account_expires_at`, `expired_at`, `mobile_verified_at`) and table names (`settings` → `fb_settings`, `messages` → `fb_message`, ...)
- **Skip tables** — always-ignored message/migration tables plus a `--tables_except` list

---

## Installation

```bash
composer require mortezamasumi/fb-copydb
```

---

## Usage

Run the copy using two existing connections:

```bash
php artisan fb-copydb --src_connection=source --dest_connection=destination
```

Or point directly at a remote database:

```bash
php artisan fb-copydb \
  --src_url=user:pass@host \
  --src_db=my_database \
  --dest_connection=local
```

When no destination is given, the application's default connection is used.

### Options

| Option | Description |
| --- | --- |
| `--src_connection=` | Source connection as defined in `database.php` |
| `--src_url=` | Source credentials in `user:pass@host` format when `src_connection` is not set |
| `--src_db=` | Source database name when `src_connection` is not set |
| `--src_driver=` | Source driver (default `mysql`) when `src_connection` is not set |
| `--dest_connection=` | Destination connection as defined in `database.php` |
| `--dest_url=` | Destination credentials in `user:pass@host` format when `dest_connection` is not set |
| `--dest_db=` | Destination database name when `dest_connection` is not set |
| `--dest_driver=` | Destination driver (default `mysql`) when `dest_connection` is not set |
| `--chunk_size=1000` | Number of rows inserted per chunk |
| `--tables_except=` | Comma-separated list of tables to skip |
| `--no-migrate` | Skip dropping the destination database and running migrations |

---

## Testing

```bash
composer test
```

---

## Support policy

| PHP | Laravel | Supported |
| --- | --- | --- |
| 8.3 | 12 | Yes |

---

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please review our [security policy](.github/SECURITY.md) and report it privately.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
