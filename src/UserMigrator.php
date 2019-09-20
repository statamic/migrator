<?php

namespace Statamic\Migrator;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Migrator\Exceptions\EmailRequiredException;

class UserMigrator extends Migrator
{
    use Concerns\MigratesSingleYamlFile;

    protected $user;

    /**
     * Migrate file.
     *
     * @param string $handle
     */
    public function migrate($handle)
    {
        $this->user = $this->getSourceYaml($handle);

        $this
            ->validateEmail()
            ->setNewPath()
            ->validateUnique()
            ->migrateUserSchema()
            ->removeOldUser($handle)
            ->saveMigratedToYaml($this->user);
    }

    /**
     * Validate email is present on user to be used as new handle.
     *
     * @throws EmailRequiredException
     * @return $this
     */
    protected function validateEmail()
    {
        if (! isset($this->user['email'])) {
            throw new EmailRequiredException;
        }

        return $this;
    }

    /**
     * Set new path to be used with new email handle.
     *
     * @return $this
     */
    protected function setNewPath()
    {
        $email = $this->user['email'];

        $this->newPath = base_path("users/{$email}.yaml");

        return $this;
    }

    /**
     * Migrate default v2 user schema to default v3 user schema.
     *
     * @return $this
     */
    protected function migrateUserSchema()
    {
        $user = collect($this->user);

        if ($user->has('first_name') || $user->has('last_name')) {
            $user['name'] = $user->only('first_name', 'last_name')->filter()->implode(' ');
        }

        $this->user = $user->except('first_name', 'last_name', 'email')->all();

        return $this;
    }

    /**
     * Remove old user file.
     *
     * @param string $handle
     * @return $this
     */
    protected function removeOldUser($handle)
    {
        if ($this->files->exists($oldFileInNewPath = base_path("users/{$handle}.yaml"))) {
            $this->files->delete($oldFileInNewPath);
        }

        return $this;
    }
}
