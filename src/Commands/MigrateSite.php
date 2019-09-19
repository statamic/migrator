<?php

namespace Statamic\Migrator\Commands;

use Statamic\Facades\YAML;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\Migrators\UserMigrator;
use Symfony\Component\Console\Input\InputOption;
use Statamic\Migrator\Migrators\FieldsetMigrator;
use Statamic\Migrator\Exceptions\AlreadyExistsException;
use Statamic\Migrator\Exceptions\EmailRequiredException;

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

        $this->getHandlesFromPath($path)->each(function ($handle) use ($migrator) {
            try {
                $migrator->overwrite($this->option('force'))->migrate($handle);
            } catch (AlreadyExistsException $exception) {
                return $this->line("<comment>Blueprint already exists:</comment> {$handle}");
            }

            $this->line("<info>Fieldset migrated:</info> {$handle}");
        });

        return $this;
    }

    protected function migrateUsers()
    {
        $migrator = UserMigrator::sourcePath($path = base_path('site/users'));

        $this->getHandlesFromPath($path)->each(function ($handle) use ($migrator) {
            try {
                $migrator->overwrite($this->option('force'))->migrate($handle);
            } catch (AlreadyExistsException $exception) {
                return $this->line("<comment>User already exists:</comment> {$handle}");
            } catch (EmailRequiredException $exception) {
                return $this->line("<error>Email field required to migrate user:</error> {$handle}");
            }

            $this->line("<info>User migrated:</info> {$handle}");
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

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force migration (file will be overwritten if already exists)'],
        ];
    }
}
