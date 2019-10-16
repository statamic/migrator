<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Migrator\Exceptions\MigratorWarningsException;

trait ThrowsFinalWarnings
{
    protected $warnings = [];

    /**
     * Add warning to be thrown at the end of a migration.
     *
     * @param string $warning
     * @param null|string $extra
     */
    protected function addWarning($warning, $extra = null)
    {
        $this->warnings[] = compact('warning', 'extra');
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
