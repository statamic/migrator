<?php

namespace Statamic\Migrator\Commands;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Console\Command as IlluminateCommand;
use Statamic\Migrator\Exceptions\MigratorErrorException;
use Statamic\Migrator\Exceptions\MigratorWarningsException;

class Command extends IlluminateCommand
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $handle = $this->hasArgument('handle') ? $this->argument('handle') : false;

        try {
            $this->runMigration($handle);
        } catch (MigratorWarningsException $exception) {
            $this->outputWarnings($exception->getWarnings());
        } catch (MigratorErrorException $exception) {
            return $this->error($exception->getMessage());
        }

        $this->clearCache();

        $descriptor = $this->migrator::descriptor();
        $handleDescriptor = $handle ? " [{$handle}]" : null;

        $this->info("{$descriptor}{$handleDescriptor} migrated successfully.");
    }

    /**
     * Run migration.
     *
     * @param string|bool $handle
     */
    protected function runMigration($handle)
    {
        $migrator = $handle
            ? $this->migrator::handle($handle)
            : $this->migrator::withoutHandle();

        $migrator
            ->overwrite($this->option('force'))
            ->migrate();
    }

    /**
     * Output warnings.
     *
     * @param \Illuminate\Support\Collection $warnings
     */
    protected function outputWarnings($warnings)
    {
        $warnings->each(function ($warning) {
            $this->comment('Warning: ' . $warning->get('warning'));

            if ($extra = $warning->get('extra')) {
                $this->line($extra);
            }
        });
    }

    /**
     * Clear cache.
     *
     * @return $this
     */
    protected function clearCache()
    {
        $this->callSilent('cache:clear');

        return $this;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['handle', InputArgument::REQUIRED, 'The handle to be migrated'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force migration (files will be overwritten if already exists at destination)'],
        ];
    }
}
