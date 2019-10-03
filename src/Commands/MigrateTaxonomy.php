<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\TaxonomyMigrator;

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
     * Runs migrator.
     *
     * @var string
     */
    protected $migrator = TaxonomyMigrator::class;
}
