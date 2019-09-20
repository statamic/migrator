<?php

namespace Statamic\Migrator;

use Statamic\Facades\YAML;
use Statamic\Support\Str;

class PagesMigrator extends Migrator
{
    /**
     * Migrate file.
     *
     * @param string $handle
     */
    public function migrate($handle)
    {
        $this->newPath = base_path("content/collections/pages");
    }
}
