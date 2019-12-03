<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Migrator\Exceptions\NotFoundException;
use Statamic\Migrator\RolesMigrator;

trait MigratesRoles
{
    /**
     * Migrate role IDs to slugs.
     *
     * @param array $roles
     * @return array
     */
    protected function migrateRoles($roles)
    {
        $rolesPath = $this->sitePath('settings/users/roles.yaml');

        if (! $this->files->exists($rolesPath)) {
            throw new NotFoundException("Roles file cannot be found at path [path].", $rolesPath);
        }

        return $this
            ->getSourceYaml($rolesPath, true)
            ->filter(function ($role, $id) use ($roles) {
                return in_array($id, $roles);
            })
            ->map(function ($role) {
                return (new RolesMigrator(null))->migrateSlug($role);
            })
            ->values()
            ->all();
    }
}
