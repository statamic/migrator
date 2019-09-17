<?php

namespace Statamic\Migrator\Commands;

use Statamic\Facades\YAML;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\Migrators\UserMigrator;
use Statamic\Migrator\Migrators\FieldsetMigrator;

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
        $this
            ->migrateFieldsets()
            ->migrateUsers();

        $this->info("Site successfully migrated!");
    }

    protected function migrateFieldsets()
    {
        $migrator = FieldsetMigrator::sourcePath($path = base_path('site/settings/fieldsets'));

        $this
            ->getHandlesFromPath($path)
            ->reject(function ($handle) {
                if ($this->files->exists(resource_path("blueprints/{$handle}.yaml"))) {
                    $this->line("<comment>Blueprint Already Exists</comment>: {$handle}");
                    return true;
                }
            })
            ->each(function ($handle) use ($migrator) {
                $migrator->migrate($handle);
                $this->line("<info>Fieldset Migrated:</info> {$handle}");
            });

        return $this;
    }

    protected function migrateUsers()
    {
        $migrator = UserMigrator::sourcePath($path = base_path('site/users'));

        $this
            ->getHandlesFromPath($path)
            ->map(function ($handle) use ($path) {
                return [
                    'old' => $handle,
                    'new' => YAML::parse($this->files->get("{$path}/{$handle}.yaml"))['email'] ?? null,
                ];
            })
            ->reject(function ($handle) {
                if (! $handle['new']) {
                    $this->line("<error>Email Field Required To Migrate User:</error> {$handle['old']}");
                    return true;
                } elseif ($this->files->exists(base_path("users/{$handle['new']}.yaml"))) {
                    $this->line("<comment>User Already Exists</comment>: {$handle['new']}");
                    return true;
                }
            })
            ->each(function ($handle) use ($migrator) {
                $migrator->migrate($handle['old']);
                $this->line("<info>User Migrated:</info> {$handle['new']}");
            });

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
