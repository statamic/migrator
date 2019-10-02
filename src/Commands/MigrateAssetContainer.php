<?php

namespace Statamic\Migrator\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Migrator\AssetContainerMigrator;
use Symfony\Component\Console\Input\InputArgument;
use Statamic\Migrator\Exceptions\NotFoundException;

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
     * Execute the console command.
     */
    public function handle()
    {
        $handle = $this->argument('handle');

        try {
            AssetContainerMigrator::sourcePath(base_path('content/assets'))->overwrite(true)->migrate($handle);
        } catch (NotFoundException $exception) {
            return $this->error("Asset container folder [{$handle}] could not be found.");
        }

        $this->info("Asset container [{$handle}] has been successfully migrated.");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['handle', InputArgument::REQUIRED, 'The asset container handle to be migrated'],
        ];
    }
}
