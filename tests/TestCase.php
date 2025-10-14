<?php

namespace Mortezamasumi\FbCopydb\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Mortezamasumi\FbCopydb\FbCopydbServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

class TestCase extends TestbenchTestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.connections.origin', [
            'driver' => 'sqlite',
            'database' => database_path('origin_testing.sqlite'),
            'prefix' => '',
        ]);

        File::put(database_path('origin_testing.sqlite'), '');

        Schema::create('test', function (Blueprint $table) {
            $table->id();
            $table->string('text');
            $table->timestamps();
        });

        Schema::connection('origin')->create('test', function (Blueprint $table) {
            $table->id();
            $table->string('text');
            $table->timestamps();
        });

        // table which not exists on source
        Schema::create('test1', function (Blueprint $table) {
            $table->id();
            $table->string('text');
            $table->timestamps();
        });

        // table which not exists on destination
        Schema::connection('origin')->create('test2', function (Blueprint $table) {
            $table->id();
            $table->string('text');
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            \Orchestra\Workbench\WorkbenchServiceProvider::class,
            FbCopydbServiceProvider::class,
        ];
    }
}
