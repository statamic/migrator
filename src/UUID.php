<?php

namespace Statamic\Migrator;

use Statamic\Support\Str;

class UUID
{
    /**
     * Generate UUID.
     *
     * @return string
     */
    public static function generate()
    {
        return (string) Str::uuid();
    }
}
