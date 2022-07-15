<?php

namespace Statamic\Migrator\Exceptions;

use Exception;

class MigratorWarningsException extends Exception
{
    protected $warnings;

    /**
     * Ensure message is required.
     *
     * @param  string  $message
     */
    public function __construct($warnings)
    {
        $this->warnings = $warnings;
    }

    /**
     * Get warnings collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getWarnings()
    {
        return collect($this->warnings)->map(function ($warning) {
            return collect($warning)->filter();
        });
    }
}
