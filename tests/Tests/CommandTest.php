<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

it('can registers the command', function () {
    expect(Artisan::all())->toHaveKey('fb-copydb');
});

it('can copy datable tables from source to destination', function () {
    $count = 10;

    $data = Collection::times($count, function (int $number) {
        return ['text' => 'text ' . $number];
    })->all();

    DB::connection('origin')->table('test')->insert($data);

    /** @var Pest $this */
    $this
        ->artisan('fb-copydb --src_connection=origin --dest_connection=testing')
        ->assertExitCode(0);

    $this->assertDatabaseCount('test', count($data));

    foreach ($data as $record) {
        $this->assertDatabaseHas('test', $record);
    }
});
