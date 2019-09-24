<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Facades\YAML;

trait MigratesFolder
{
    /**
     * Copy source files.
     *
     * return $this
     */
    protected function copySourceFiles()
    {
        if ($this->sourcePath === $this->newPath) {
            return $this;
        }

        $this->files->copyDirectory($this->sourcePath, $this->newPath);

        return $this;
    }
}
