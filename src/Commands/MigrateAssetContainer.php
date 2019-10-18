<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\AssetContainerMigrator;
use Symfony\Component\Console\Input\InputOption;

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

    /**
     * Run migration.
     *
     * @param string $handle
     */
    protected function runMigration($handle)
    {
        $this->migrator::handle($handle)
            ->overwrite($this->option('force'))
            ->metaOnly($this->option('meta-only'))
            ->migrate();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['meta-only', null, InputOption::VALUE_NONE, 'Migrate asset container meta only'],
        ]);
    }
}
