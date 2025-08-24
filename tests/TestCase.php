<?php

namespace Mortezamasumi\FbCopydb\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Filament\FilamentServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use Mortezamasumi\FbCopydb\Tests\Services\FbCopydbPanelProvider;
use Mortezamasumi\FbCopydb\FbCopydbServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

use function Orchestra\Testbench\default_migration_path;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Factory::guessFactoryNamesUsing(
        //     fn (string $modelName) => 'Mortezamasumi\\FbCopydb\\Database\\Factories\\'.class_basename($modelName).'Factory'
        // );
    }

    protected function defineEnvironment($app)
    {
        // config()->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYfiBfCuSThJ5SK8=');
        // config()->set('database.default', 'testing');
        // config()->set('queue.batching.database', 'testing');
        // config()->set('auth.providers.users.model', '\Tests\Models\User');

        /*
         * $migration = include __DIR__.'/../database/migrations/create_page-test_table.php.stub';
         * $migration->up();
         */
        // View::addLocation(__DIR__.'/resources/views');
        // View::addLocation(__DIR__.'/../resources/views');
    }

    protected function defineDatabaseMigrations()
    {
        /** @var Orchestra $this */
        $this->loadMigrationsFrom(default_migration_path());
        $this->loadMigrationsFrom(default_migration_path().'/notifications');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            FbCopydbServiceProvider::class,
        ];
    }
}
