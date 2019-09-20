<?php

namespace Statamic\Migrator;

use Illuminate\Console\Application as Artisan;
use Statamic\Extend\ServiceProvider as BaseProvider;

class ServiceProvider extends BaseProvider
{
    protected $commands = [
        Commands\MigrateCollection::class,
        Commands\MigrateFieldset::class,
        Commands\MigrateSite::class,
        Commands\MigratePages::class,
        Commands\MigrateUser::class,
    ];

    public function boot()
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolveCommands($this->commands);
        });
    }
}
