<?php

namespace Statamic\Migrator\Concerns;

trait PreparesPathFolder
{
    /**
     * Prepare folder for file path.
     *
     * @param string $path
     */
    protected function prepareFolder($path)
    {
        $folder = $this->getFolderFromPath($path);

        if (! $this->files->exists($folder)) {
            $this->files->makeDirectory($folder, 0755, true);
        }
    }

    /**
     * Delete folder for file path.
     *
     * @param string $path
     */
    protected function deleteFolder($path)
    {
        $folder = $this->getFolderFromPath($path);

        if ($this->files->exists($folder)) {
            $this->files->deleteDirectory($folder);
        }
    }

    /**
     * Get folder from path.
     *
     * @param string $path
     * @return string
     */
    protected function getFolderFromPath($path)
    {
        return preg_replace('/(.*)\/[^\/]+\.[^\/]+/', '$1', $path);
    }
}
