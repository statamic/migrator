<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\FormMigrator;

class MigrateForm extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:form';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 form';

    /**
     * Runs migrator.
     *
     * @var string
     */
    protected $migrator = FormMigrator::class;
}
