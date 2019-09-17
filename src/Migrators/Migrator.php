<?php

namespace Statamic\Migrator\Migrators;

use Exception;
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
