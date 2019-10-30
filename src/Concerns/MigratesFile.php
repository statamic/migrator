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
            throw new NotFoundException("{$descriptor} cannot be found at path [path].", $path);
        }

        return $this->files->get($path);
    }

    /**
     * Save migrated file contents to yaml.
     *
     * @param array|\Illuminate\Support\Collection $migrated
     * @param string|null $path
     * @return $this
     */
    protected function saveMigratedYaml($migrated, $path = null)
    {
        $migrated = collect($migrated)->all();

        return $this->saveMigratedContents(YAML::dump($migrated), $path);
    }

    /**
     * Save migrate file contents with yaml front matter.
     *
     * @param array|\Illuminate\Support\Collection $migrated
     * @param string|null $path
     * @return $this
     */
    protected function saveMigratedWithYamlFrontMatter($migrated, $path = null)
    {
        $content = collect($migrated)->get('content');
        $migrated = collect($migrated)->except('content')->all();

        return $this->saveMigratedContents(YAML::dump($migrated, $content), $path);
    }

    /**
     * Save migrated file contents.
     *
     * @param string $migrated
     * @param string|null $path
     * @return $this
     */
    protected function saveMigratedContents($migrated, $path = null)
    {
        $path = $this->normalizePath($path ?? $this->newPath);

        $folder = preg_replace('/(.*)\/[^\/]*/', '$1', $path);

        if (! $this->files->exists($folder)) {
            $this->files->makeDirectory($folder);
        }

        $this->files->put($path, $migrated);

        return $this;
    }

    /**
     * Allow our YAML wrapper to migrate a yaml file, updating document content and Spyc formatting as necessary.
     *
     * @param string|null $path
     */
    public function updateYaml($path)
    {
        $path = $this->normalizePath($path ?? $this->newPath);

        $this->saveMigratedYaml($this->getSourceYaml($path), $path);
    }
}
