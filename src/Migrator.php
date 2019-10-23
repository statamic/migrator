<?php

namespace Statamic\Migrator;

use Statamic\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\Exceptions\NotFoundException;
use Statamic\Migrator\Exceptions\AlreadyExistsException;

abstract class Migrator
{
    use Concerns\NormalizesPath;

    protected $handle;
    protected $newPath;
    protected $overwrite = false;

    /**
     * Instantiate migrator.
     *
     * @param string $handle
     */
    public function __construct($handle)
    {
        $this->handle = $handle;
        $this->files = app(Filesystem::class);
    }

    /**
     * Instantiate migrator on handle.
     *
     * @param string $handle
     * @return static
     */
    public static function handle($handle)
    {
        return new static($handle);
    }

    /**
     * Instantiate migrator without handle.
     *
     * @return static
     */
    public static function withoutHandle()
    {
        return new static(null);
    }

    /**
     * Get descriptor for use in command output.
     *
     * @return string
     */
    public static function descriptor()
    {
        $extractedFromClassName = preg_replace('/.*\\\(\w+)Migrator$/', '$1', get_called_class());

        return Str::modifyMultiple($extractedFromClassName, ['studlyToWords', 'ucfirst']);
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
     * Perform migration.
     */
    abstract public function migrate();

    /**
     * Set new path.
     *
     * @param string $path
     * @return $this
     */
    protected function setNewPath($path)
    {
        $this->newPath = $path;

        return $this;
    }

    /**
     * Get site path.
     *
     * @param string|null $append
     * @return string
     */
    protected function sitePath($append = null)
    {
        return collect([base_path('site'), $append])->filter()->implode('/');
    }

    /**
     * Get new path.
     *
     * @param string|null $append
     * @return string
     */
    protected function newPath($append = null)
    {
        return collect([$this->newPath, $append])->filter()->implode('/');
    }

    /**
     * Validate unique.
     *
     * @throws AlreadyExistsException
     * @return $this
     */
    protected function validateUnique()
    {
        if ($this->overwrite) {
            return $this;
        }

        $descriptor = static::descriptor();

        collect($this->uniquePaths())
            ->map(function ($path) {
                return $this->normalizePath($path);
            })
            ->filter(function ($path) {
                return $this->pathExists($path);
            })
            ->each(function ($path) use ($descriptor) {
                throw new AlreadyExistsException("{$descriptor} already exists at [path].", $path);
            });

        return $this;
    }

    /**
     * Check if path exists (with files, if directory).
     *
     * @param mixed $path
     */
    protected function pathExists($path)
    {
        $pathExists = $this->files->exists($path);

        return $this->files->isDirectory($path)
            ? $pathExists && $this->files->files($path)
            : $pathExists;
    }

    /**
     * Specify unique paths that shouldn't be overwritten.
     *
     * @return array
     */
    protected function uniquePaths()
    {
        return [
            $this->newPath
        ];
    }

    /**
     * Copy directory from site path to new path.
     *
     * @param string $sitePath
     * @param string|null $newPath
     * @return $this
     */
    protected function copyDirectoryFromSiteToNewPath($sitePath, $newPath = null)
    {
        $this->files->copyDirectory($this->sitePath($sitePath), $this->newPath($newPath));

        return $this;
    }

    // /**
    //  * Copy file from site path to new path.
    //  *
    //  * @param string $sitePath
    //  * @param string|null $newPath
    //  * @return $this
    //  */
    // protected function copyFileFromSiteToNewPath($sitePath, $newPath = null)
    // {
    //     $this->files->copy($this->sitePath($sitePath), $this->newPath($newPath));

    //     return $this;
    // }
}
