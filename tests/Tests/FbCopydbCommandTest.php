<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Mortezamasumi\FbCopydb\Commands\FbCopydbCommand;
use Mortezamasumi\FbCopydb\Exceptions\InvalidDatabaseException;

it('fails when the source url is missing and no source connection is given', function () {
    expect(fn () => Artisan::call('fb-copydb --dest_connection=testing'))
        ->toThrow(InvalidArgumentException::class);
});

it('fails when the source database name is missing', function () {
    expect(fn () => Artisan::call('fb-copydb --src_url=user:pass@host --dest_connection=testing'))
        ->toThrow(InvalidArgumentException::class);
});

it('fails when the destination database name is missing and a destination url is given', function () {
    expect(fn () => Artisan::call('fb-copydb --src_connection=origin --dest_url=user:pass@host'))
        ->toThrow(InvalidArgumentException::class);
});

it('fails when the destination database cannot be created', function () {
    Config::set('database.connections.unsupported', [
        'driver' => 'pgsql',
        'database' => 'nope',
    ]);

    expect(fn () => Artisan::call('fb-copydb --src_connection=origin --dest_connection=unsupported'))
        ->toThrow(InvalidDatabaseException::class);
});

it('registers a mysql connection built from a url', function () {
    $command = app(FbCopydbCommand::class);

    $name = $command->makeConnection('user:pass@localhost', 'mydb', 'mysql');

    expect($name)->toBeString()
        ->and(Config::get("database.connections.$name.driver"))->toBe('mysql')
        ->and(Config::get("database.connections.$name.host"))->toBe('localhost')
        ->and(Config::get("database.connections.$name.database"))->toBe('mydb')
        ->and(Config::get("database.connections.$name.username"))->toBe('user')
        ->and(Config::get("database.connections.$name.password"))->toBe('pass');
});

it('registers a sqlite connection using the given database name', function () {
    $command = app(FbCopydbCommand::class);

    $name = $command->makeConnection('', 'copydb_test.sqlite', 'sqlite');

    expect(Config::get("database.connections.$name.driver"))->toBe('sqlite')
        ->and(Config::get("database.connections.$name.database"))->toBe(database_path('copydb_test.sqlite'));
});

it('fails when the connection url does not match the expected format', function () {
    $command = app(FbCopydbCommand::class);

    expect(fn () => $command->makeConnection('invalid', 'mydb', 'mysql'))
        ->toThrow(InvalidArgumentException::class);
});

it('fails when the connection url has an empty host', function () {
    $command = app(FbCopydbCommand::class);

    expect(fn () => $command->makeConnection('user:pass@', 'mydb', 'mysql'))
        ->toThrow(InvalidArgumentException::class);
});

it('fails when the connection url has an empty user', function () {
    $command = app(FbCopydbCommand::class);

    expect(fn () => $command->makeConnection('@host', 'mydb', 'mysql'))
        ->toThrow(InvalidArgumentException::class);
});

it('accepts an in-memory sqlite destination without creating a file', function () {
    $command = app(FbCopydbCommand::class);

    expect($command->createDestinationDatabase('testing'))->toBeTrue();
});

it('creates a sqlite file when the destination database file is missing', function () {
    Config::set('database.connections.file_dest', [
        'driver' => 'sqlite',
        'database' => database_path('file_dest.sqlite'),
        'prefix' => '',
    ]);

    File::delete(database_path('file_dest.sqlite'));

    $command = app(FbCopydbCommand::class);

    expect($command->createDestinationDatabase('file_dest'))->toBeTrue()
        ->and(file_exists(database_path('file_dest.sqlite')))->toBeTrue();
});

it('returns false for unsupported destination drivers', function () {
    Config::set('database.connections.unsupported_driver', [
        'driver' => 'pgsql',
        'database' => 'x',
    ]);

    $command = app(FbCopydbCommand::class);

    expect($command->createDestinationDatabase('unsupported_driver'))->toBeFalse();
});

it('maps legacy users columns when converting table data', function () {
    $command = app(FbCopydbCommand::class);

    $converted = $command->convertTableData('users', collect([
        (object) [
            'id' => 1,
            'account_expires_at' => '2026-01-01',
            'mobile_verified_at' => '2026-01-01',
            'otp_code' => '123456',
            'otp_expires_at' => '2026-01-01',
        ],
    ]))->first();

    expect($converted)->toHaveKey('expiration_date', '2026-01-01')
        ->and($converted)->toHaveKey('email_verified_at', '2026-01-01')
        ->and($converted)->not->toHaveKey('account_expires_at')
        ->and($converted)->not->toHaveKey('mobile_verified_at')
        ->and($converted)->not->toHaveKey('otp_code')
        ->and($converted)->not->toHaveKey('otp_expires_at');
});

it('maps expired_at to expiration_date when converting users data', function () {
    $command = app(FbCopydbCommand::class);

    $converted = $command->convertTableData('users', collect([
        (object) ['id' => 1, 'expired_at' => '2026-01-01'],
    ]))->first();

    expect($converted)->toHaveKey('expiration_date', '2026-01-01')
        ->and($converted)->not->toHaveKey('expired_at');
});

it('leaves data of other tables untouched when converting', function () {
    $command = app(FbCopydbCommand::class);

    $data = collect([(object) ['id' => 1, 'name' => 'test']]);

    expect($command->convertTableData('posts', $data))->toBe($data);
});

it('maps legacy table names', function () {
    $command = app(FbCopydbCommand::class);

    expect($command->convertTablesNames('mars_questions'))->toBe('fb_mars')
        ->and($command->convertTablesNames('settings'))->toBe('fb_settings')
        ->and($command->convertTablesNames('messages'))->toBe('fb_message')
        ->and($command->convertTablesNames('mars'))->toBe('fb_mars')
        ->and($command->convertTablesNames('fb_message_user'))->toBe('fb_message_users');
});

it('leaves unknown table names untouched when converting', function () {
    $command = app(FbCopydbCommand::class);

    expect($command->convertTablesNames('posts'))->toBe('posts');
});
