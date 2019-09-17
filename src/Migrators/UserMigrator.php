<?php

namespace Statamic\Migrator\Migrators;

use Statamic\Support\Arr;
use Statamic\Support\Str;

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

        $newHandle = $user['email'];

        unset($user['email']);

        $this->saveMigratedToYaml(base_path("users/{$newHandle}.yaml"), $user);
    }
}
