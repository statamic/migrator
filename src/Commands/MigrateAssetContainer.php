<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\AssetContainerMigrator;

class MigrateAssetContainer extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:asset-container';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 asset container';

    /**
     * Runs migrator.
     *
     * @var string
     */
    protected $migrator = AssetContainerMigrator::class;
}
