<?php

namespace Statamic\Migrator\Migrators;

use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\Exceptions\NotFoundException;
use Statamic\Migrator\Exceptions\AlreadyExistsException;

abstract class Migrator
{
    protected $sourcePath;
    protected $newPath;
    protected $overwrite = false;

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
     * Get new path.
     *
     * @param string|null $append
     * @return string
     */
    public function newPath($append = null)
    {
        return collect([$this->newPath, $append])->filter()->implode('/');
    }

    /**
     * Set whether files should be overwritten.
     *
     * @param bool $overwrite
     * @return $this
     */
    public function overwrite($overwrite)
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * Validate unique.
     *
     * @throws AlreadyExistsException
     * @return $this
     */
    protected function validateUnique()
    {
        if (! $this->overwrite && $this->files->exists($this->newPath)) {
            throw new AlreadyExistsException;
        }

        return $this;
    }

    /**
     * Migrate file.
     *
     * @param string $handle
     */
    abstract function migrate($handle);
}
