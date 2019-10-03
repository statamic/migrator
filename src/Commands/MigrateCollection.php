<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\CollectionMigrator;
use Statamic\Migrator\Exceptions\NotFoundException;

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
     * Execute the console command.
     */
    public function handle()
    {
        $handle = $this->argument('handle');

        try {
            CollectionMigrator::handle($handle)->overwrite($this->option('force'))->migrate();
        } catch (NotFoundException $exception) {
            return $this->error("Collection folder [{$handle}] could not be found.");
        }

        $this->info("Collection [{$handle}] has been successfully migrated.");
    }
}
