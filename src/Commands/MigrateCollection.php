<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\CollectionMigrator;

class MigrateCollection extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 collection';

    /**
     * Runs migrator.
     *
     * @var string
     */
    protected $migrator = CollectionMigrator::class;
}
