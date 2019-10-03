<?php

namespace Statamic\Migrator\Exceptions;

class AlreadyExistsException extends MigratorException
{
    public function __construct($message, $path = null)
    {
        $this->message = $this->injectPathInMessage($message, $path);
    }
}
