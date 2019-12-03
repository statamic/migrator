<?php

namespace Statamic\Migrator;

use Statamic\Support\Str;

class RolesMigrator extends Migrator
{
    use Concerns\MigratesFile;

    protected $roles;

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
            ->saveMigratedYaml($this->roles, $this->newPath());
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
            case preg_match('/^(pages):(view|edit|create|delete|reorder)$/', $permission, $matches):
            case preg_match('/^collections:(\w+):(view|edit|create|delete)$/', $permission, $matches):
                return $this->migrateCollectionPermission($matches[1], $matches[2]);
            case preg_match('/^taxonomies:(\w+):(view|edit|create|delete)$/', $permission, $matches):
                return $this->migrateTaxonomyPermission($matches[1], $matches[2]);
            case preg_match('/^assets:(\w+):(view|edit|create|delete)$/', $permission, $matches):
                return $this->migrateAssetContainerPermission($matches[1], $matches[2]);
            case preg_match('/^globals:(\w+):(view|edit)$/', $permission, $matches):
                return $this->migrateGlobalSetPermission($matches[1], $matches[2]);
            case $permission === 'updater':
                return 'view updates';
            case $permission === 'updater:update':
                return 'perform updates';
            case preg_match('/^users:(view|edit|create|delete|edit-passwords|edit-roles)$/', $permission, $matches):
                return $this->migrateUserPermission($matches[1]);
            case $permission === 'forms':
                return $this->migrateFormPermission();
            default:
                return $permission;
        }
    }

    /**
     * Migrate collection permission.
     *
     * @param string $collection
     * @param string $action
     * @return string|array
     */
    protected function migrateCollectionPermission($collection, $action)
    {
        if ($action === 'edit' && $collection !== 'pages') {
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
     * Migrate asset container permission.
     *
     * @param string $container
     * @param string $action
     * @return string|array
     */
    protected function migrateAssetContainerPermission($container, $action)
    {
        if ($action === 'edit') {
            return [
                "edit {$container} assets",
                "move {$container} assets",
                "rename {$container} assets",
            ];
        }

        if ($action === 'create') {
            return "upload {$container} assets";
        }

        return "{$action} {$container} assets";
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

    /**
     * Migrate access form permission.
     *
     * @return array
     */
    protected function migrateFormPermission()
    {
        return collect($this->files->files($this->sitePath('settings/formsets')))
            ->map
            ->getFilenameWithoutExtension()
            ->flatMap(function ($handle) {
                return [
                    "view {$handle} form submissions",
                    "delete {$handle} form submissions",
                ];
            })
            ->all();
    }
}
