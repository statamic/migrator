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
     * @param bool $overwrite
     */
    public function migrate($handle, $overwrite = false)
    {
        $user = $this->getSourceYaml($handle);

        $newHandle = $user['email'] ?? null;

        if (! $newHandle) {
            throw new EmailRequiredException;
        }

        $newPath = base_path("users/{$newHandle}.yaml");

        if (! $overwrite && $this->files->exists($newPath)) {
            throw new AlreadyExistsException;
        }

        unset($user['email']);

        $this->saveMigratedToYaml($newPath, $user);
    }
}
