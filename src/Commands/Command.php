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
        $handle = $this->argument('handle');

        try {
            $this->migrator::handle($handle)
                ->overwrite($this->option('force'))
                ->migrate();
        } catch (MigratorWarningsException $exception) {
            $this->outputWarnings($exception->getWarnings());
        } catch (MigratorErrorException $exception) {
            return $this->error($exception->getMessage());
        }

        $descriptor = $this->migrator::descriptor();

        $this->info("{$descriptor} [{$handle}] has been successfully migrated.");
    }

    /**
     * Output warnings.
     *
     * @param \Illuminate\Support\Collection $warnings
     */
    protected function outputWarnings($warnings)
    {
        $warnings->each(function ($warning) {
            $this->error($warning->get('warning'));

            if ($extra = $warning->get('extra')) {
                $this->line($extra);
            }
        });
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
