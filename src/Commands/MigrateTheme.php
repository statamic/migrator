<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\ThemeMigrator;

class MigrateTheme extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:theme';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 theme to views folder';

    /**
     * Runs migrator.
     *
     * @var string
     */
    protected $migrator = ThemeMigrator::class;
}
