<?php

namespace Statamic\Migrator\Commands;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Path;
use Statamic\Migrator\AssetContainerMigrator;
use Statamic\Migrator\CollectionMigrator;
use Statamic\Migrator\Concerns;
use Statamic\Migrator\Exceptions\AlreadyExistsException;
use Statamic\Migrator\Exceptions\MigratorErrorException;
use Statamic\Migrator\Exceptions\MigratorSkippedException;
use Statamic\Migrator\Exceptions\MigratorWarningsException;
use Statamic\Migrator\FieldsetMigrator;
use Statamic\Migrator\FormMigrator;
use Statamic\Migrator\GlobalSetMigrator;
use Statamic\Migrator\GroupsMigrator;
use Statamic\Migrator\PagesMigrator;
use Statamic\Migrator\RolesMigrator;
use Statamic\Migrator\SettingsMigrator;
use Statamic\Migrator\TaxonomyMigrator;
use Statamic\Migrator\ThemeMigrator;
use Statamic\Migrator\UserMigrator;
use Statamic\Migrator\YAML;
use Symfony\Component\Console\Input\InputOption;

class MigrateSite extends Command
{
    use Concerns\GetsFieldsetHandles,
        Concerns\GetsSettings,
        Concerns\SubmitsStats,
        RunsInPlease;

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
     * Warning count.
     *
     * @var int
     */
    protected $warningCount = 0;

    /**
     * Error count.
     *
     * @var int
     */
    protected $errorCount = 0;

    /**
     * Log file path.
     *
     * @var string
     */
    protected $logPath;

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
            ->setLogPath()
            ->migrateFieldsets()
            ->migrateCollections()
            ->migratePages()
            ->migrateTaxonomies()
            ->migrateAssetContainers()
            ->migrateGlobalSets()
            ->migrateForms()
            ->migrateUsers()
            ->migrateRoles()
            ->migrateGroups()
            ->migrateSettings()
            ->migrateTheme()
            ->clearCache();

        if (! $this->option('without-stats-submission')) {
            $this->submitStats();
        }

        $this->outputSummary();
    }

    /**
     * Set log path for current migration attempt.
     *
     * @return $this
     */
    protected function setLogPath()
    {
        $timestamp = time();

        $this->logPath = base_path("site-migration-log-{$timestamp}.yaml");

        return $this;
    }

    /**
     * Migrate fieldsets.
     *
     * @return $this
     */
    protected function migrateFieldsets()
    {
        $this->getFieldsetHandles()->each(function ($handle) {
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
     * Migrate asset containers.
     *
     * @return $this
     */
    protected function migrateAssetContainers()
    {
        $this->getFileHandlesFromPath(base_path('site/content/assets'))->each(function ($handle) {
            $this->runMigratorOnHandle(AssetContainerMigrator::class, $handle);
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
     * Migrate roles.
     *
     * @return $this
     */
    protected function migrateRoles()
    {
        $this->runMigratorWithoutHandle(RolesMigrator::class, 'roles');

        return $this;
    }

    /**
     * Migrate groups.
     *
     * @return $this
     */
    protected function migrateGroups()
    {
        $this->runMigratorWithoutHandle(GroupsMigrator::class, 'groups');

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
        $this->runMigratorOnHandle(SettingsMigrator::class, 'assets');
        $this->runMigratorOnHandle(SettingsMigrator::class, 'cp');
        $this->runMigratorOnHandle(SettingsMigrator::class, 'routes');
        $this->runMigratorOnHandle(SettingsMigrator::class, 'system');
        $this->runMigratorOnHandle(SettingsMigrator::class, 'users');

        // TODO: Run this once each settings migration is 'complete'
        // $this->getFileHandlesFromPath(base_path('site/settings'))->each(function ($handle) {
        //     $this->runMigratorOnHandle(SettingsMigrator::class, $handle);
        // });

        return $this;
    }

    /**
     * Migrate theme.
     *
     * @return $this
     */
    protected function migrateTheme()
    {
        $this->runMigratorOnHandle(ThemeMigrator::class, $this->getSetting('theming.theme', 'redwood'));

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
            return preg_replace('/.*\/([^\/]+)/', '$1', Path::resolve($path));
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
        } catch (MigratorWarningsException $warningsException) {
            $this->outputMigrationWarnings($descriptor, $handle, $warningsException->getWarnings());
            $this->logMigrationWarnings($descriptor, $handle, $warningsException->getWarnings());
            $this->warningCount++;
        } catch (AlreadyExistsException $exception) {
            $this->line("<comment>{$descriptor} already exists:</comment> {$handle}");
            $this->skippedCount++;
        } catch (MigratorSkippedException $exception) {
            $this->line("<comment>{$descriptor} could not be migrated:</comment> {$handle}");
            $this->line($exception->getMessage());
            $this->skippedCount++;
        } catch (MigratorErrorException $exception) {
            $this->line("<error>{$descriptor} could not be migrated:</error> {$handle}");
            $this->line($exception->getMessage());
            $this->logError($descriptor, $handle, $exception->getMessage());
            $this->errorCount++;
        } catch (Exception $exception) {
            $this->line("<error>{$descriptor} exception:</error> {$handle}");
            $this->line($exception->getMessage());
            $this->logException($descriptor, $handle, $exception);
            $this->errorCount++;
        }

        if (! isset($exception)) {
            $this->line("<info>{$descriptor} migrated successfully:</info> {$handle}");
            $this->successCount++;
        }
    }

    /**
     * Output warnings.
     *
     * @param string $descriptor
     * @param string $handle
     * @param \Illuminate\Support\Collection $warnings
     */
    protected function outputMigrationWarnings($descriptor, $handle, $warnings)
    {
        $warnings->each(function ($warning) use ($descriptor, $handle) {
            $this->line("<comment>{$descriptor} migration warning:</comment> {$handle}");
            $this->line($warning->get('warning'));

            if ($extra = $warning->get('extra')) {
                $this->line($extra);
            }
        });
    }

    /**
     * Log warnings.
     *
     * @param string $descriptor
     * @param string $handle
     * @param \Illuminate\Support\Collection $warnings
     */
    protected function logMigrationWarnings($descriptor, $handle, $warnings)
    {
        $warnings = $warnings->map(function ($warning) use ($descriptor, $handle) {
            return collect([
                'migration' => $descriptor,
                'handle' => $handle,
                'warning' => $warning->get('warning'),
                'info' => $warning->get('extra'),
            ])->filter()->all();
        })->all();

        $this->files->append($this->logPath, YAML::dump($warnings));
    }

    /**
     * Log error.
     *
     * @param string $descriptor
     * @param string $handle
     * @param string $error
     */
    protected function logError($descriptor, $handle, $error)
    {
        $this->files->append($this->logPath, YAML::dump([[
            'migration' => $descriptor,
            'handle' => $handle,
            'error' => $error,
        ]]));
    }

    /**
     * Log exception.
     *
     * @param string $descriptor
     * @param string $handle
     * @param string $exception
     */
    protected function logException($descriptor, $handle, $exception)
    {
        $this->files->append($this->logPath, YAML::dump([[
            'migration' => $descriptor,
            'handle' => $handle,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTrace(),
        ]]));
    }

    /**
     * Submit stats.
     */
    protected function submitStats()
    {
        $this->attemptSubmitStats([
            'command' => $this->name,
            'skipped' => $this->skippedCount,
            'errors' => $this->errorCount,
            'warnings' => $this->warningCount,
            'successful' => $this->successCount,
        ]);
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
            "{$this->errorCount} ".($this->errorCount == 1 ? 'error' : 'errors'),
            "{$this->warningCount} ".($this->warningCount == 1 ? 'warning' : 'warnings'),
            "{$this->successCount} successful",
        ]);
    }

    /**
     * Output site migration summary.
     */
    protected function outputSummary()
    {
        $statsSummary = $this->getStats()->implode(', ');
        $logFile = str_replace(base_path().'/', '', $this->logPath);

        $this->line('---');

        $this->line("<info>Site migration complete:</info> {$statsSummary}");

        if ($this->errorCount || $this->warningCount) {
            $this->line("<comment>View log:</comment> {$logFile}");
        }
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

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['without-stats-submission', null, InputOption::VALUE_NONE, 'Do not submit anonymous migration statistics to statamic.com'],
        ]);
    }
}
