<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\RolesMigrator;

class MigrateRoles extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 user roles';

    /**
     * Runs migrator.
     *
     * @var string
     */
    protected $migrator = RolesMigrator::class;

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
