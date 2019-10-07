<?php

namespace Statamic\Migrator;

use Illuminate\Console\Application as Artisan;
use Statamic\Extend\ServiceProvider as BaseProvider;

class ServiceProvider extends BaseProvider
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
        Commands\MigrateUser::class,
    ];

    public function boot()
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolveCommands($this->commands);
        });
    }
}
