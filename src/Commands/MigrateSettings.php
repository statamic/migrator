<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\SettingsMigrator;

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
}
