<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\GlobalSetMigrator;

class MigrateGlobalSet extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:global-set';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 global set';

    /**
     * Runs migrator.
     *
     * @var string
     */
    protected $migrator = GlobalSetMigrator::class;
}
