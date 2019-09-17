<?php

namespace Statamic\Migrator\Migrators;

use Exception;
use Statamic\Facades\YAML;
use Illuminate\Filesystem\Filesystem;

abstract class Migrator
{
    protected $sourcePath;

    /**
     * Instantiate migrator.
     *
     * @param string $path
     */
    public function __construct($sourcePath)
    {
        $this->sourcePath = $sourcePath;
        $this->files = app(Filesystem::class);
    }

    /**
     * Instantiate migrator.
     *
     * @param string $path
     * @return static
     */
    public static function sourcePath($sourcePath)
    {
        return new static($sourcePath);
    }

    /**
     * Migrate file.
     *
     * @param string $handle
     */
    abstract function migrate($handle);

    /**
     * Get yaml contents.
     *
     * @param string $handle
     * @return array
     */
    protected function getSourceYaml($handle)
    {
        return YAML::parse($this->getSourceContents($handle));
    }

    /**
     * Save migrated file contents to yaml.
     *
     * @param string $path
     * @param array $migrated
     */
    protected function saveMigratedToYaml($path, $migrated)
    {
        $this->saveMigratedContents($path, YAML::dump($migrated));
    }

    /**
     * Get file contents.
     *
     * @param string $handle
     * @return string
     * @throws Exception
     */
    protected function getSourceContents($handle)
    {
        $path = $this->getSourcePath($handle);
        $relativePath = str_replace(base_path() . '/', '', $path);

        if (! $this->files->exists($path)) {
            throw new Exception("Cannot find file [{$relativePath}].");
        }

        return $this->files->get($path);
    }

    /**
     * Save migrated file contents.
     *
     * @param string $path
     * @param string $migrated
     */
    public function saveMigratedContents($path, $migrated)
    {
        $folder = preg_replace('/(.*)\/[^\/]*/', '$1', $path);

        if (! $this->files->exists($folder)) {
            $this->files->makeDirectory($folder);
        }

        $this->files->put($path, $migrated);
    }

    /**
     * Get file path.
     *
     * @param string $handle
     * @return string
     */
    protected function getSourcePath($handle)
    {
        return "{$this->sourcePath}/{$handle}.yaml";
    }
}
