<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Migrator\YAML;
use Statamic\Migrator\Exceptions\NotFoundException;

trait MigratesSingleFile
{
    /**
     * Get yaml contents.
     *
     * @param string $sitePath
     * @return array
     */
    protected function getSourceYamlFromSite($sitePath)
    {
        return YAML::parse($this->getSourceContentsFromSite($sitePath));
    }

    /**
     * Save migrated file contents to yaml.
     *
     * @param string $path
     * @param array $migrated
     */
    protected function saveMigratedYaml($migrated)
    {
        $this->saveMigratedContents(YAML::dump($migrated));
    }

    /**
     * Get file contents.
     *
     * @param string $sitePath
     * @return string
     * @throws NotFoundException
     */
    protected function getSourceContentsFromSite($sitePath)
    {
        $path = $this->sitePath($sitePath);
        $relativePath = str_replace(base_path() . '/', '', $path);

        if (! $this->files->exists($path)) {
            throw new NotFoundException("Cannot find file [{$relativePath}].");
        }

        return $this->files->get($path);
    }

    /**
     * Save migrated file contents.
     *
     * @param string $path
     * @param string $migrated
     */
    public function saveMigratedContents($migrated)
    {
        $folder = preg_replace('/(.*)\/[^\/]*/', '$1', $this->newPath);

        if (! $this->files->exists($folder)) {
            $this->files->makeDirectory($folder);
        }

        $this->files->put($this->newPath, $migrated);
    }
}
