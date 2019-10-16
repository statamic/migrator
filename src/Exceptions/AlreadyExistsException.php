<?php

namespace Statamic\Migrator\Exceptions;

class AlreadyExistsException extends MigratorErrorException
{
    public function __construct($message, $path = null)
    {
        $this->message = $this->injectPathInMessage($message, $path);
    }
}
