<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\FieldsetPartialMigrator;

class MigrateFieldsetPartial extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:fieldset-partial';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 fieldset partial to importable fieldset';

    /**
     * Runs migrator.
     *
     * @var string
     */
    protected $migrator = FieldsetPartialMigrator::class;
}
