<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

it('registers the fb-copydb command', function () {
    expect(Artisan::all())->toHaveKey('fb-copydb');
});

it('copies table data from the source to the destination connection', function () {
    $count = 10;

    $data = Collection::times($count, function (int $number) {
        return ['text' => 'text '.$number];
    })->all();

    DB::connection('origin')->table('test')->insert($data);

    $this
        ->artisan('fb-copydb --src_connection=origin --dest_connection=testing')
        ->assertExitCode(0);

    $this->assertDatabaseCount('test', count($data));

    foreach ($data as $record) {
        $this->assertDatabaseHas('test', $record);
    }
});

it('copies to the default connection when no destination is given', function () {
    DB::connection('origin')->table('test')->insert(['text' => 'default destination']);

    $this
        ->artisan('fb-copydb --src_connection=origin')
        ->assertExitCode(0);

    $this->assertDatabaseHas('test', ['text' => 'default destination']);
});

it('copies data without running migrations when the no-migrate option is given', function () {
    DB::connection('origin')->table('test')->insert(['text' => 'no migrate']);

    $this
        ->artisan('fb-copydb --src_connection=origin --dest_connection=testing --no-migrate')
        ->assertExitCode(0);

    $this->assertDatabaseHas('test', ['text' => 'no migrate']);
});

it('skips tables listed in the tables_except option', function () {
    Schema::connection('origin')->create('skipme', function (Blueprint $table) {
        $table->id();
        $table->string('text');
    });

    DB::connection('origin')->table('skipme')->insert(['text' => 'skip me']);

    $this
        ->artisan('fb-copydb --src_connection=origin --dest_connection=testing --tables_except=skipme')
        ->expectsOutputToContain('Ignore processing of table skipme')
        ->assertExitCode(0);
});

it('always ignores the legacy message and migration tables', function () {
    Schema::connection('origin')->create('messages', function (Blueprint $table) {
        $table->id();
        $table->string('text');
    });

    DB::connection('origin')->table('messages')->insert(['text' => 'ignored']);

    $this
        ->artisan('fb-copydb --src_connection=origin --dest_connection=testing')
        ->expectsOutputToContain('Ignore processing of table messages')
        ->assertExitCode(0);
});

it('completes when the source database has no tables', function () {
    Config::set('database.connections.empty_source', [
        'driver' => 'sqlite',
        'database' => database_path('empty_source.sqlite'),
        'prefix' => '',
    ]);

    File::put(database_path('empty_source.sqlite'), '');

    $this
        ->artisan('fb-copydb --src_connection=empty_source --dest_connection=testing')
        ->assertExitCode(0);
});
