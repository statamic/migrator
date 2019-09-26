<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Migrator\YAML;

trait MigratesFolder
{
    /**
     * Copy source files.
     *
     * @param string|null $handle
     * @return $this
     */
    protected function copySourceFiles($handle = null)
    {
        $sourcePath = collect([$this->sourcePath, $handle])->filter()->implode('/');

        if ($sourcePath === $this->newPath) {
            return $this;
        }

        $this->files->copyDirectory($sourcePath, $this->newPath);

        return $this;
    }
}
