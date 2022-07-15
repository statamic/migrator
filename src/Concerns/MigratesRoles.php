<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Migrator\Exceptions\NotFoundException;
use Statamic\Migrator\GroupsMigrator;
use Statamic\Migrator\RolesMigrator;

trait MigratesRoles
{
    /**
     * Migrate role IDs to slugs.
     *
     * @param  array  $roles
     * @return array
     */
    protected function migrateRoles($roles)
    {
        $path = $this->sitePath('settings/users/roles.yaml');

        if (! $this->files->exists($path)) {
            throw new NotFoundException('Roles file cannot be found at path [path].', $path);
        }

        return $this
            ->getSourceYaml($path, true)
            ->filter(function ($role, $id) use ($roles) {
                return in_array($id, $roles);
            })
            ->map(function ($role) {
                return (new RolesMigrator(null))->migrateSlug($role);
            })
            ->values()
            ->all();
    }

    /**
     * Migrate group IDs to slugs.
     *
     * @param  array  $groups
     * @return array
     */
    protected function migrateGroups($groups)
    {
        $path = $this->sitePath('settings/users/groups.yaml');

        if (! $this->files->exists($path)) {
            throw new NotFoundException('Groups file cannot be found at path [path].', $path);
        }

        return $this
            ->getSourceYaml($path, true)
            ->filter(function ($group, $id) use ($groups) {
                return in_array($id, $groups);
            })
            ->map(function ($group) {
                return (new GroupsMigrator(null))->migrateSlug($group);
            })
            ->values()
            ->all();
    }
}
