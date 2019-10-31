<?php

namespace Statamic\Migrator;

use Statamic\Providers\AddonServiceProvider;
use Illuminate\Console\Application as Artisan;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        Commands\MigrateAssetContainer::class,
        Commands\MigrateCollection::class,
        Commands\MigrateFieldset::class,
        Commands\MigrateForm::class,
        Commands\MigrateGlobalSet::class,
        Commands\MigratePages::class,
        Commands\MigrateSettings::class,
        Commands\MigrateSite::class,
        Commands\MigrateTaxonomy::class,
        Commands\MigrateTheme::class,
        Commands\MigrateUser::class,
    ];

    public function boot()
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolveCommands($this->commands);
        });
    }
}
