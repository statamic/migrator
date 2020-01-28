<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\RoutesMigrator;

class MigrateRoutes extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 routes';

    /**
     * Runs migrator.
     *
     * @var string
     */
    protected $migrator = RoutesMigrator::class;

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }
}
