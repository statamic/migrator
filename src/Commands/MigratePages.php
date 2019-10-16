<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\PagesMigrator;
use Statamic\Migrator\Exceptions\MigratorErrorException;

class MigratePages extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 pages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            PagesMigrator::withoutHandle()->overwrite($this->option('force'))->migrate();
        } catch (MigratorErrorException $exception) {
            return $this->error($exception->getMessage());
        }

        $this->info("Pages collection has been successfully migrated.");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }
}
