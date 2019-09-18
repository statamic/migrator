<?php

namespace Statamic\Migrator\Migrators;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Migrator\Exceptions\AlreadyExistsException;
use Statamic\Migrator\Exceptions\EmailRequiredException;

class UserMigrator extends Migrator
{
    /**
     * Migrate file.
     *
     * @param string $handle
     */
    public function migrate($handle)
    {
        $user = $this->getSourceYaml($handle);

        $newHandle = $user['email'] ?? null;

        if (! $newHandle) {
            throw new EmailRequiredException;
        }

        $newPath = base_path("users/{$newHandle}.yaml");

        if ($this->files->exists($newPath)) {
            throw new AlreadyExistsException;
        }

        unset($user['email']);

        $this->saveMigratedToYaml($newPath, $user);
    }
}
