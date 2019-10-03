<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\FieldsetMigrator;
use Symfony\Component\Console\Input\InputArgument;
use Statamic\Migrator\Exceptions\NotFoundException;

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
            FieldsetMigrator::handle($handle)->overwrite($this->option('force'))->migrate();
        } catch (NotFoundException $exception) {
            return $this->error("Fieldset [{$handle}] could not be found.");
        }

        $this->info("Fieldset [{$handle}] has been successfully migrated to a blueprint.");
    }
}
