<?php

namespace Mortezamasumi\FbCopydb;

use Livewire\Features\SupportTesting\Testable;
use Mortezamasumi\FbCopydb\Commands\FbCopydbCommand;
use Mortezamasumi\FbCopydb\Testing\TestsFbCopydb;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FbCopydbServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fb-copydb';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasCommands($this->getCommands());
    }

    public function packageBooted(): void
    {
        Testable::mixin(new TestsFbCopydb);
    }

    protected function getCommands(): array
    {
        return [
            FbCopydbCommand::class,
        ];
    }
}
