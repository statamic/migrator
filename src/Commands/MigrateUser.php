<?php

namespace Statamic\Migrator\Commands;

use Statamic\Console\RunsInPlease;
use Statamic\Migrator\UserMigrator;
use Symfony\Component\Console\Input\InputArgument;
use Statamic\Migrator\Exceptions\NotFoundException;
use Statamic\Migrator\Exceptions\EmailRequiredException;

class MigrateUser extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:migrate:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate v2 user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $handle = $this->argument('handle');

        try {
            UserMigrator::handle($handle)->overwrite($this->option('force'))->migrate();
        } catch (NotFoundException $exception) {
            return $this->error("User [{$handle}] could not be found.");
        } catch (EmailRequiredException $exception) {
            return $this->error("User [{$handle}] cannot be migrated because it does not contain an `email`.");
        }

        $this->info("User [{$handle}] has been successfully migrated.");
    }
}
