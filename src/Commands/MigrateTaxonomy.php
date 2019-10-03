<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\TaxonomyMigrator;
use Symfony\Component\Console\Input\InputArgument;
use Statamic\Migrator\Exceptions\NotFoundException;

class MigrateTaxonomy extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:taxonomy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 taxonomy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $handle = $this->argument('handle');

        try {
            TaxonomyMigrator::sourcePath(base_path('content/taxonomies'))->overwrite(true)->migrate($handle);
        } catch (NotFoundException $exception) {
            return $this->error("Taxonomy folder [{$handle}] could not be found.");
        }

        $this->info("Taxonomy [{$handle}] has been successfully migrated.");
    }
}
