<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\GroupsMigrator;

class MigrateGroups extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 user groups';

    /**
     * Runs migrator.
     *
     * @var string
     */
    protected $migrator = GroupsMigrator::class;

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
