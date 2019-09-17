<?php

namespace Statamic\Migrator\Commands;

use Exception;
use Statamic\Migrator\Migrators\FieldsetMigrator;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Symfony\Component\Console\Input\InputArgument;

class MigrateFieldset extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:fieldset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 fieldset to blueprint';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $handle = $this->argument('handle');

        try {
            FieldsetMigrator::sourcePath(resource_path('blueprints'))->migrate($handle);
        } catch (Exception $exception) {
            return $this->error($exception->getMessage());
        }

        $this->info("Fieldset [{$handle}.yaml] has been successfully migrated to a blueprint.");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['handle', InputArgument::REQUIRED, 'The fieldset handle to be migrated'],
        ];
    }
}
