<?php

namespace Statamic\Migrator;

use Statamic\Migrator\Exceptions\MigratorSkippedException;
use Statamic\Support\Str;

class GroupsMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\MigratesRoles;

    protected $groups;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(resource_path('users/groups.yaml'))
            ->validate()
            ->parseGroups()
            ->migrateGroups()
            ->saveMigratedYaml($this->groups, $this->newPath());
    }

    /**
     * Validate whether groups file has been modified.
     *
     * @return $this
     *
     * @throws MigratorSkippedException
     */
    protected function validate()
    {
        if ($this->overwrite || ! $this->files->exists($this->newPath())) {
            return $this;
        }

        $currentGroups = $this->files->get($this->newPath());
        $defaultGroups = $this->files->get('vendor/statamic/cms/resources/users/groups.yaml');

        if ($currentGroups !== $defaultGroups) {
            throw new MigratorSkippedException('Groups file [resources/users/groups.yaml] has already been modified.');
        }

        return $this;
    }

    /**
     * Parse groups.
     *
     * @return $this
     */
    protected function parseGroups()
    {
        $this->groups = $this->getSourceYaml('settings/users/groups.yaml', true);

        return $this;
    }

    /**
     * Migrate groups.
     *
     * @return $this
     */
    protected function migrateGroups()
    {
        $this->groups = $this->groups
            ->keyBy(function ($group) {
                return $this->migrateSlug($group);
            })
            ->map(function ($group) {
                return $this->migrateGroup($group);
            });

        return $this;
    }

    /**
     * Migrate slug.
     *
     * @param  array  $group
     * @return string
     */
    public function migrateSlug($group)
    {
        return $group['slug'] ?? Str::snake($group['title']);
    }

    /**
     * Migrate group.
     *
     * @param  array  $group
     * @return array
     */
    protected function migrateGroup($group)
    {
        $group['roles'] = $this->migrateRoles($group['roles']);

        unset($group['users']);

        return $group;
    }
}
