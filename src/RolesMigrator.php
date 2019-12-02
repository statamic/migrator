<?php

namespace Statamic\Migrator;

use Statamic\Support\Str;

class RolesMigrator extends Migrator
{
    use Concerns\MigratesFile;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(resource_path('users/roles.yaml'))
            // ->validateUnique()
            ->parseRoles()
            ->migrateRoles()
            ->saveMigratedYaml($this->roles, $this->newPath())
            ;
    }

    /**
     * Parse roles.
     *
     * @return $this
     */
    protected function parseRoles()
    {
        $this->roles = $this->getSourceYaml('settings/users/roles.yaml', true);

        return $this;
    }

    /**
     * Migrate roles.
     *
     * @return $this
     */
    protected function migrateRoles()
    {
        $this->roles = $this->roles
            ->keyBy(function ($role) {
                return $this->migrateSlug($role);
            })
            ->map(function ($role) {
                return $this->migrateRole($role);
            });

        return $this;
    }

    /**
     * Migrate slug.
     *
     * @param array $role
     * @return string
     */
    public function migrateSlug($role)
    {
        return $role['slug'] ?? Str::snake($role['title']);
    }

    /**
     * Migrate role.
     *
     * @param array $role
     * @return array
     */
    protected function migrateRole($role)
    {
        $role['permissions'] = collect($role['permissions'] ?? [])
            ->map(function ($permission) {
                return $this->migratePermission($permission);
            })
            ->flatten()
            ->filter()
            ->values()
            ->all();

        return $role;
    }

    /**
     * Migrate permission.
     *
     * @param string $permission
     * @return string|array
     */
    protected function migratePermission($permission)
    {
        switch (true) {
            case $permission === 'cp:access':
                return 'access cp';
            case preg_match('/^(pages):(view|edit|create|delete)$/', $permission, $matches):
            case preg_match('/^collections:(\w+):(view|edit|create|delete)$/', $permission, $matches):
                return $this->migrateCollectionPermission($matches[1], $matches[2]);
            case preg_match('/^taxonomies:(\w+):(view|edit|create|delete)$/', $permission, $matches):
                return $this->migrateTaxonomyPermission($matches[1], $matches[2]);
            case preg_match('/^globals:(\w+):(view|edit)$/', $permission, $matches):
                return $this->migrateGlobalSetPermission($matches[1], $matches[2]);
            case $permission === 'updater':
                return 'view updates';
            case $permission === 'updater:update':
                return 'perform updates';
            case preg_match('/^users:(view|edit|create|delete|edit-passwords|edit-roles)$/', $permission, $matches):
                return $this->migrateUserPermission($matches[1]);
            default:
                return $permission;
        }
    }

    /**
     * Migrate collection permission.
     *
     * @param string $collection
     * @param string $action
     * @return string
     */
    protected function migrateCollectionPermission($collection, $action)
    {
        if ($action === 'edit') {
            return [
                "edit {$collection} entries",
                "reorder {$collection} entries",
            ];
        }

        if ($action === 'create') {
            return [
                "create {$collection} entries",
                "publish {$collection} entries",
            ];
        }

        return "{$action} {$collection} entries";
    }

    /**
     * Migrate taxonomy permission.
     *
     * @param string $taxonomy
     * @param string $action
     * @return string
     */
    protected function migrateTaxonomyPermission($taxonomy, $action)
    {
        return "{$action} {$taxonomy} terms";
    }

    /**
     * Migrate global set permission.
     *
     * @param string $globalSet
     * @param string $action
     * @return string|null
     */
    protected function migrateGlobalSetPermission($globalSet, $action)
    {
        if ($action === 'view') {
            return null;
        }

        return "{$action} {$globalSet} globals";
    }

    /**
     * Migrate user permission.
     *
     * @param string $action
     * @return string
     */
    protected function migrateUserPermission($action)
    {
        if ($action === 'edit-passwords') {
            return 'change passwords';
        }

        if ($action === 'edit-roles') {
            return 'edit roles';
        }

        return "{$action} users";
    }
}
