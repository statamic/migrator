<?php

namespace Statamic\Migrator\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Migrator\UserMigrator;
use Statamic\Migrator\PagesMigrator;
use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\FieldsetMigrator;
use Statamic\Migrator\TaxonomyMigrator;
use Statamic\Migrator\CollectionMigrator;
use Symfony\Component\Console\Input\InputOption;
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
            ->migrateCollections()
            ->migratePages()
            // ->migrateTaxonomies()
            ->migrateUsers();

        $this->info("Site successfully migrated!");
    }

    /**
     * Migrate fieldsets.
     *
     * @return $this
     */
    protected function migrateFieldsets()
    {
        $path = base_path('site/settings/fieldsets');

        $migrator = FieldsetMigrator::sourcePath($path)->overwrite($this->option('force'));

        $this->getFileHandlesFromPath($path)->each(function ($handle) use ($migrator) {
            try {
                $migrator->migrate($handle);
            } catch (AlreadyExistsException $exception) {
                return $this->line("<comment>Blueprint already exists:</comment> {$handle}");
            }

            $this->line("<info>Fieldset migrated:</info> {$handle}");
        });

        return $this;
    }

    /**
     * Migrate collections.
     *
     * @return $this
     */
    protected function migrateCollections()
    {
        $path = base_path('site/content/collections');

        $migrator = CollectionMigrator::sourcePath($path)->overwrite($this->option('force'));

        $this->getFolderHandlesFromPath($path)->each(function ($handle) use ($migrator) {
            try {
                $migrator->migrate($handle);
            } catch (AlreadyExistsException $exception) {
                return $this->line("<comment>Collection already exists:</comment> {$handle}");
            }

            $this->line("<info>Collection migrated:</info> {$handle}");
        });

        return $this;
    }

    /**
     * Migrate pages.
     *
     * @return $this
     */
    protected function migratePages()
    {
        $path = base_path('site/content/pages');

        $migrator = PagesMigrator::sourcePath($path)->overwrite($this->option('force'));

        try {
            $migrator->migrate($handle = 'pages');
        } catch (AlreadyExistsException $exception) {
            $this->line("<comment>Pages collection/structure already exists:</comment> {$handle}");
        }

        if (! isset($exception)) {
            $this->line("<info>Pages collection/structure migrated:</info> {$handle}");
        }

        return $this;
    }

    /**
     * Migrate taxonomies.
     *
     * @return $this
     */
    protected function migrateTaxonomies()
    {
        $path = base_path('site/content/taxonomies');

        $migrator = TaxonomyMigrator::sourcePath($path)->overwrite($this->option('force'));

        // $this->getFileHandlesFromPath($path)->each(function ($handle) use ($migrator) {
        //     try {
        //         $migrator->migrate($handle);
        //     } catch (AlreadyExistsException $exception) {
        //         return $this->line("<comment>Pages collection/structure already exists:</comment> {$handle}");
        //     }

        //     $this->line("<info>Pages migrated:</info> {$handle}");
        // });

        $this->getFileHandlesFromPath($path)->each(function ($handle) {
            $this->line("<info>Taxonomy migrated:</info> {$handle}");
        });

        return $this;
    }

    /**
     * Migrate users.
     *
     * @return $this
     */
    protected function migrateUsers()
    {
        $path = base_path('site/users');

        $migrator = UserMigrator::sourcePath($path)->overwrite($this->option('force'));

        $this->getFileHandlesFromPath($path)->each(function ($handle) use ($migrator) {
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

    /**
     * Get file handles from path.
     *
     * @param string $path
     * @return \Illuminate\Support\Collection
     */
    protected function getFileHandlesFromPath($path)
    {
        if (! $this->files->exists($path)) {
            return collect();
        }

        return collect($this->files->files($path))
            ->map
            ->getFilenameWithoutExtension();
    }

    /**
     * Get folder handles from path.
     *
     * @param string $path
     * @return \Illuminate\Support\Collection
     */
    protected function getFolderHandlesFromPath($path)
    {
        if (! $this->files->exists($path)) {
            return collect();
        }

        return collect($this->files->directories($path))->map(function ($path) {
            return preg_replace('/.*\/([^\/]+)/', '$1', $path);
        });
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
