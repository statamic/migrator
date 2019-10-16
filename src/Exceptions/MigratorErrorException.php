<?php

namespace Statamic\Migrator\Exceptions;

use Exception;
use Statamic\Migrator\Concerns\NormalizesPath;

class MigratorErrorException extends Exception
{
    use NormalizesPath;

    /**
     * Ensure message is required.
     *
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Ensure relative path.
     *
     * @param string $path
     * @return
     */
    protected function ensureRelativePath($path)
    {
        return str_replace(base_path() . '/', '', $this->normalizePath($path));
    }

    /**
     * Inject $path replacement into message '[path]'.
     *
     * @param string $message
     * @param string $path
     * @return string
     */
    protected function injectPathInMessage($message, $path)
    {
        return str_replace('[path]', '[' . $this->ensureRelativePath($path) . ']', $message);
    }
}
