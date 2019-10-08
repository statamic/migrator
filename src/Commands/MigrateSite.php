<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\FormMigrator;
use Statamic\Migrator\UserMigrator;
use Statamic\Migrator\PagesMigrator;
use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\FieldsetMigrator;
use Statamic\Migrator\TaxonomyMigrator;
use Statamic\Migrator\GlobalSetMigrator;
use Statamic\Migrator\CollectionMigrator;
use Statamic\Migrator\Exceptions\MigratorException;
use Statamic\Migrator\Exceptions\AlreadyExistsException;

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
     * Success count.
     *
     * @var int
     */
    protected $successCount = 0;

    /**
     * Skipped count.
     *
     * @var int
     */
    protected $skippedCount = 0;

    /**
     * Error count.
     *
     * @var int
     */
    protected $errorCount = 0;

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
            ->migrateTaxonomies()
            ->migrateGlobalSets()
            ->migrateUsers();

        $this->line('<info>Site migration complete:</info> ' . $this->getStats()->implode(', '));
    }

    /**
     * Migrate fieldsets.
     *
     * @return $this
     */
    protected function migrateFieldsets()
    {
        $this->getFileHandlesFromPath(base_path('site/settings/fieldsets'))->each(function ($handle) {
            $this->runMigratorOnHandle(FieldsetMigrator::class, $handle);
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
        $this->getFolderHandlesFromPath(base_path('site/content/collections'))->each(function ($handle) {
            $this->runMigratorOnHandle(CollectionMigrator::class, $handle);
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
        $this->runMigratorWithoutHandle(PagesMigrator::class, 'pages');

        return $this;
    }

    /**
     * Migrate taxonomies.
     *
     * @return $this
     */
    protected function migrateTaxonomies()
    {
        $this->getFileHandlesFromPath(base_path('site/content/taxonomies'))->each(function ($handle) {
            $this->runMigratorOnHandle(TaxonomyMigrator::class, $handle);
        });

        return $this;
    }

    /**
     * Migrate global sets.
     *
     * @return $this
     */
    protected function migrateGlobalSets()
    {
        $this->getFileHandlesFromPath(base_path('site/content/globals'))->each(function ($handle) {
            $this->runMigratorOnHandle(GlobalSetMigrator::class, $handle);
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
        $this->getFileHandlesFromPath(base_path('site/users'))->each(function ($handle) {
            $this->runMigratorOnHandle(UserMigrator::class, $handle);
        });

        return $this;
    }

    /**
     * Migrate forms.
     *
     * @return $this
     */
    protected function migrateForms()
    {
        $this->getFileHandlesFromPath(base_path('site/settings/formsets'))->each(function ($handle) {
            $this->runMigratorOnHandle(FormMigrator::class, $handle);
        });

        return $this;
    }

    /**
     * Migrate settings.
     *
     * @return $this
     */
    protected function migrateSettings()
    {
        //

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
     * Run migrator on handle.
     *
     * @param mixed $migrator
     * @param mixed $handle
     */
    protected function runMigratorOnHandle($migrator, $handle)
    {
        $descriptor = $migrator::descriptor();

        $this->tryMigration(function () use ($migrator, $handle) {
            $migrator::handle($handle)->overwrite($this->option('force'))->migrate();
        }, $descriptor, $handle);
    }

    /**
     * Run migrator without handle (but can still pass one for command output).
     *
     * @param string $migrator
     * @param string|null $handle
     */
    protected function runMigratorWithoutHandle($migrator, $handle = null)
    {
        $descriptor = $migrator::descriptor();

        $this->tryMigration(function () use ($migrator) {
            $migrator::withoutHandle()->overwrite($this->option('force'))->migrate();
        }, $descriptor, $handle);
    }

    /**
     * Try migration, with exception handling, and statistic recording.
     *
     * @param \Closure $migration
     */
    protected function tryMigration($migration, $descriptor, $handle)
    {
        try {
            $migration();
        } catch (AlreadyExistsException $exception) {
            $this->line("<comment>{$descriptor} already exists:</comment> {$handle}");
            $this->skippedCount++;
        } catch (MigratorException $exception) {
            $this->line("<error>{$descriptor} could not be migrated:</error> {$handle}");
            $this->line($exception->getMessage());
            $this->errorCount++;
        }

        if (! isset($exception)) {
            $this->line("<info>{$descriptor} successfully migrated:</info> {$handle}");
            $this->successCount++;
        }
    }

    /**
     * Get stats.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getStats()
    {
        return collect([
            "{$this->skippedCount} skipped",
            "{$this->errorCount} " . ($this->errorCount == 1 ? 'error' : 'errors'),
            "{$this->successCount} successful",
        ]);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }
}
