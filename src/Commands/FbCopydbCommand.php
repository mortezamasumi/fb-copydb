<?php

namespace Mortezamasumi\FbCopydb\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mortezamasumi\FbCopydb\Exceptions\InvalidDatabaseException;
use Symfony\Component\Console\Attribute\AsCommand;
use Exception;
use InvalidArgumentException;

#[AsCommand(
    name: 'fb-copydb',
    description: 'Copy database',
)]
class FbCopydbCommand extends Command
{
    protected $signature = <<<SIG
            fb-copydb
            {--src_connection= : The source connection if defined in database.php}
            {--src_url= : The source connection auth in format user:pass@host if src_connection not defined}
            {--src_db= : The source database name if src_connection not defined}
            {--src_driver= : The source driver if src_connection not defined}
            {--dest_connection= : The destination connection if defined in database.php}
            {--dest_url= : The destination connection auth in format user:pass@host if dest_connection not defined}
            {--dest_db= : The destination database name if dest_connection not defined}
            {--dest_driver= : The destination driver if dest_connection not defined}
            {--old_filament_base=false : Fix users columns and table names from old filament-base}
            {--chunk_size=1000 : Chunk size to copy data}
            {--tables_except= : List of tables to except to copy}
        SIG;

    public function getConnections(): array
    {
        if ($this->option('src_connection')) {
            $sourceConnection = $this->option('src_connection');
        } else {
            if (! $this->option('src_url')) {
                throw new InvalidArgumentException('Source url must be set if not src_connection defined');
            }

            if (! $this->option('src_db')) {
                throw new InvalidArgumentException('Source db must be set if not src_connection defined');
            }

            $sourceConnection = $this->makeConnection(
                $this->option('src_url'),
                $this->option('src_db'),
                $this->option('src_driver') ?? 'mysql'
            );
        }

        if ($this->option('dest_connection')) {
            $destinationConnection = $this->option('dest_connection');
        } else {
            if (! $this->option('dest_url')) {
                $destinationConnection = Config::get('database.default');
            } else {
                if (! $this->option('dest_db')) {
                    throw new InvalidArgumentException('Destination db must be set if not dest_connection defined');
                }

                $destinationConnection = $this->makeConnection(
                    $this->option('dest_url'),
                    $this->option('dest_db'),
                    $this->option('dest_driver') ?? 'mysql'
                );
            }
        }

        return [$sourceConnection, $destinationConnection];
    }

    public function makeConnection(string $url, string $db, string $driver): string
    {
        $connectionName = Str::of('connection_')->append(time());

        if ($driver === 'sqlite') {
            Config::set('database.connections.'.$connectionName, [
                'driver' => 'sqlite',
                'database' => database_path($connectionName),
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);
        } else {
            if (! Str::contains($url, '@')) {
                throw new InvalidArgumentException('Invalid connection string format. Expected user:pass@host');
            }

            [$credentials, $host] = explode('@', $url, 2);

            if (empty($host)) {
                throw new InvalidArgumentException('Host cannot be emptyt');
            }

            $parts = explode(':', $credentials, 2);

            $user = $parts[0] ?? null;
            $pass = $parts[1] ?? null;

            if (empty($user)) {
                throw new InvalidArgumentException('users cannot be empty');
            }

            Config::set('database.connections.'.$connectionName, [
                'driver' => $driver,
                'host' => $host,
                'port' => '3306',
                'database' => $db,
                'username' => $user,
                'password' => $pass,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ]);
        }

        return $connectionName;
    }

    public function createDestinationDatabase($dest): bool
    {
        $config = Config::get("database.connections.{$dest}");

        try {
            switch ($config['driver']) {
                case 'sqlite':
                    $databasePath = $config['database'];

                    if ($databasePath === ':memory:') {
                        return true;
                    }

                    if (! file_exists($databasePath)) {
                        touch($databasePath);
                        chmod($databasePath, 0755);
                    }

                    return file_exists($databasePath);

                case 'mysql':
                    $dbName = $config['database'];

                    Config::set('database.connections.'.$dest.'.database', null);

                    DB::reconnect($dest);

                    $result = DB::connection($dest)
                        ->select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$dbName]);

                    if (empty($result)) {
                        DB::connection($dest)->statement("CREATE DATABASE `$dbName`");
                        $this->info("Database '$dbName' created successfully.");
                    } else {
                        $this->info("Database '$dbName' already exists.");
                    }

                    Config::set('database.connections.'.$dest.'.database', $dbName);
                    DB::reconnect($dest);

                    return true;

                case 'pgsql':
                    return false;

                    $originalDbName = $config['database'];

                    $tempConfig = $config;
                    unset($tempConfig['database']);

                    DB::purge($dest);
                    Config::set("database.connections.{$dest}", $tempConfig);
                    DB::reconnect($dest);

                    $created = false;

                    $result = DB::connection($dest)
                        ->select('SELECT 1 FROM pg_database WHERE datname = ?', [$originalDbName]);
                    if (empty($result)) {
                        DB::connection($dest)
                            ->statement("CREATE DATABASE \"{$originalDbName}\"");
                        $created = true;
                    }

                    DB::purge($dest);
                    Config::set("database.connections.{$dest}", $config);
                    DB::reconnect($dest);

                    if ($created) {
                        $this->info("Database '$originalDbName' created successfully.");
                    } else {
                        $this->info("Database '$originalDbName' already exists.");
                    }

                    return true;

                default:
                    return false;
            }
        } catch (QueryException $e) {
            return false;
        }
    }

    public function migrateDestination($destinationConnection): void
    {
        $temp = Config::get('database.default');
        Config::set('database.default', $destinationConnection);
        Artisan::call('migrate');
        Config::set('database.default', $temp);
    }

    public function convertTableData($table, $data): Collection
    {
        if ($table === 'users') {
            $data = collect($data->toArray())->map(function ($item) {
                $item = (array) $item;

                if (array_key_exists('account_expires_at', $item)) {
                    $item['expiration_date'] = $item['account_expires_at'];
                    unset($item['account_expires_at']);
                }

                if (array_key_exists('expired_at', $item)) {
                    $item['expiration_date'] = $item['expired_at'];
                    unset($item['expired_at']);
                }

                if (array_key_exists('mobile_verified_at', $item)) {
                    $item['email_verified_at'] = $item['mobile_verified_at'];
                    unset($item['mobile_verified_at']);
                }

                if (array_key_exists('otp_code', $item)) {
                    unset($item['otp_code']);
                }

                if (array_key_exists('otp_expires_at', $item)) {
                    unset($item['otp_expires_at']);
                }

                return $item;
            });
        }

        return $data;
    }

    public function convertTablesNames($table): string
    {
        return match ($table) {
            'mars_questions' => 'fb_mars',
            'settings' => 'fb_settings',
            'messages' => 'fb_message',
            'mars' => 'fb_mars',
            'fb_message_user' => 'fb_message_users',
            default => $table,
        };
    }

    public function ignoreTables(): array
    {
        return array_merge(
            [
                'migrations',
                'messages',
                'fb_messages',
                'fb_message_user',
                'fb_message_users',
            ],
            explode(',', $this->option('tables_except'))
        );
    }

    public function handle()
    {
        [$sourceConnection, $destinationConnection] = $this->getConnections();

        if (! $this->createDestinationDatabase($destinationConnection)) {
            throw new InvalidDatabaseException;
        }

        $this->migrateDestination($destinationConnection);

        $sourceSqlite = Config::get('database.connections.'.$sourceConnection.'.driver') === 'sqlite';

        if ($sourceSqlite) {
            $tables = array_column(
                DB::connection($sourceConnection)
                    ->getSchemaBuilder()
                    ->getTables(),
                'name'
            );
        } else {
            $tables = collect(DB::connection($sourceConnection)->select('SHOW TABLES'));

            $tables = $tables->pluck(array_key_first((array) $tables->first()))->all();
        }

        $destSqlite = Config::get('database.connections.'.$destinationConnection.'.driver') === 'sqlite';

        if ($destSqlite) {
            DB::connection($destinationConnection)->statement('PRAGMA FOREIGN_KEY_CHECKS=0;');
        } else {
            DB::connection($destinationConnection)->statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        foreach ($tables as $table) {
            if (in_array($table, $this->ignoreTables())) {
                $this->info("Ignore processing of table $table.");

                continue;
            }

            $this->info("Start processing of table $table.");

            if ($sourceSqlite) {
                $columns = collect(DB::connection($sourceConnection)
                        ->select('PRAGMA table_info(`'.$table.'`)'))
                    ->filter(function ($column) {
                        return strpos($column->Extra ?? '', 'VIRTUAL') === false &&
                            strpos($column->Extra ?? '', 'STORED') === false;
                    })
                    ->pluck('name')
                    ->toArray();
            } else {
                $columns = collect(DB::connection($sourceConnection)
                        ->select("SHOW FULL COLUMNS FROM $table"))
                    ->filter(function ($column) {
                        return strpos($column->Extra ?? '', 'VIRTUAL') === false &&
                            strpos($column->Extra ?? '', 'STORED') === false;
                    })
                    ->pluck('Field')
                    ->toArray();
            }

            $data = DB::connection($sourceConnection)->table($table)->select($columns)->get();

            $progressBar = $this->output->createProgressBar($data->count());

            $progressBar->start();

            if ($data->isNotEmpty()) {
                $data = $this->convertTableData($table, $data);

                $table = $this->convertTablesNames($table);

                try {
                    DB::connection($destinationConnection)->table($table)->truncate();

                    $data
                        ->chunk($this->option('chunk_size'))
                        ->each(
                            function ($items) use ($destinationConnection, $table, $progressBar) {
                                DB::connection($destinationConnection)
                                    ->table($table)
                                    ->insert(json_decode(json_encode($items), true));

                                $progressBar->advance($items->count());
                            }
                        );
                } catch (Exception $e) {
                    $this->info("Error accessing of destination table $table with message: ".$e->getMessage());
                }
            }

            $progressBar->finish();

            $this->info(PHP_EOL."Finished data copy for table $table.");
        }

        if ($destSqlite) {
            DB::connection($destinationConnection)->statement('PRAGMA FOREIGN_KEY_CHECKS=1;');
        } else {
            DB::connection($destinationConnection)->statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info('Database copy completed successfully!');

        return self::SUCCESS;
    }
}
