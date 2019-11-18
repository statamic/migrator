<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\SettingsMigrator;
use Symfony\Component\Console\Input\InputArgument;

class MigrateSettings extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 settings';

    /**
     * Runs migrator.
     *
     * @var string
     */
    protected $migrator = SettingsMigrator::class;

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['handle', InputArgument::OPTIONAL, 'The handle to be migrated'],
        ];
    }
}
