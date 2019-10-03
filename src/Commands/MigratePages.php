<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\PagesMigrator;
use Symfony\Component\Console\Input\InputArgument;
use Statamic\Migrator\Exceptions\NotFoundException;

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
            PagesMigrator::withoutHandle()->overwrite($this->option('force'))->migrate('pages');
        } catch (NotFoundException $exception) {
            return $this->error("Pages collection folder could not be found.");
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
