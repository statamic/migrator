<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Migrator\Exceptions\MigratorWarningsException;

trait ThrowsFinalWarnings
{
    protected $warnings = [];

    /**
     * Add warning to be thrown at the end of a migration.
     *
     * @param  string  $warning
     * @param  null|string  $extra
     * @return $this
     */
    protected function addWarning($warning, $extra = null)
    {
        $this->warnings[] = compact('warning', 'extra');

        return $this;
    }

    /**
     * Merge warnings from another thrown migrator warnings exception.
     *
     * @param  MigratorWarningsException  $exception
     * @return $this
     */
    protected function mergeFromWarningsException(MigratorWarningsException $exception)
    {
        $exception->getWarnings()->each(function ($warning) {
            $this->addWarning(
                $warning->get('warning'),
                $warning->get('extra')
            );
        });

        return $this;
    }

    /**
     * Throw final warnings.
     *
     * @throws MigratorWarningsException
     */
    protected function throwFinalWarnings()
    {
        if (count($this->warnings)) {
            throw new MigratorWarningsException($this->warnings);
        }
    }
}
