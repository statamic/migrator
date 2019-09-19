<?php

namespace Statamic\Migrator\Migrators;

use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\Exceptions\NotFoundException;

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
     * @param bool $overwrite
     */
    abstract function migrate($handle, $overwrite = false);
}
