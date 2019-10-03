<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Support\Str;
use Statamic\Migrator\YAML;
use Statamic\Migrator\Exceptions\NotFoundException;

trait MigratesFile
{
    use NormalizesPath;

    /**
     * Get yaml contents from relative site path, or absolute path.
     *
     * @param string $path
     * @return array|\Illuminate\Support\Collection
     */
    protected function getSourceYaml($path, $collection = false)
    {
        $contents = YAML::parse($this->getSourceContents($path));

        return $collection ? collect($contents) : $contents;
    }

    /**
     * Get file contents from relative site path, or absolute path.
     *
     * @param string $path
     * @return string
     * @throws NotFoundException
     */
    protected function getSourceContents($path)
    {
        $descriptor = static::descriptor();

        $path = Str::startsWith($path, '/')
            ? $path
            : $this->sitePath($path);

        if (! $this->files->exists($path)) {
            throw new NotFoundException("{$descriptor} cannot be found at [path].", $path);
        }

        return $this->files->get($path);
    }

    /**
     * Save migrated file contents to yaml.
     *
     * @param array|\Illuminate\Support\Collection $migrated
     * @param string|null $path
     */
    protected function saveMigratedYaml($migrated, $path = null)
    {
        $this->saveMigratedContents(YAML::dump(collect($migrated)->all()), $path);
    }

    /**
     * Save migrated file contents.
     *
     * @param string $migrated
     * @param string|null $path
     */
    public function saveMigratedContents($migrated, $path = null)
    {
        $path = $this->normalizePath($path ?? $this->newPath);

        $folder = preg_replace('/(.*)\/[^\/]*/', '$1', $path);

        if (! $this->files->exists($folder)) {
            $this->files->makeDirectory($folder);
        }

        $this->files->put($path, $migrated);
    }
}
