<?php

namespace Statamic\Migrator\Commands;

use Illuminate\Console\Command;
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
            PagesMigrator::sourcePath(base_path('content/collections/pages'))->overwrite(true)->migrate('pages');
        } catch (NotFoundException $exception) {
            return $this->error("Pages collection folder could not be found.");
        }

        $this->info("Pages collection has been successfully migrated.");
    }
}
