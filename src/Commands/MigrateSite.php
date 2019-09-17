<?php

namespace Statamic\Migrator\Commands;

use Statamic\Migrator\Migrators\FieldsetMigrator;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Facades\YAML;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class MigrateSite extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:site';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate complete v2 site folder';

    /**
     * Create a new controller creator command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->migrateFieldsets();

        $this->info("Site successfully migrated!");
    }

    protected function migrateFieldsets()
    {
        $migrator = FieldsetMigrator::sourcePath($path = base_path('site/settings/fieldsets'));

        foreach ($this->getHandlesFromPath($path) as $handle) {
            $migrator->migrate($handle);
            $this->line("<info>Fieldset Migrated:</info> {$handle}");
        }

        return $this;
    }

    protected function getHandlesFromPath($path)
    {
        if (! $this->files->exists($path)) {
            return collect();
        }

        return collect($this->files->files($path))
            ->map
            ->getFilenameWithoutExtension();
    }
}
