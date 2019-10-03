<?php

namespace Statamic\Migrator;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Illuminate\Support\Facades\Validator;
use Statamic\Migrator\Exceptions\InvalidEmailException;

class UserMigrator extends Migrator
{
    use Concerns\MigratesSingleFile;

    protected $user;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->parseUser()
            ->validateEmail()
            ->setNewPathWithEmailAsHandle()
            ->validateUnique()
            ->migrateUserSchema()
            ->saveMigratedYaml($this->user);
    }

    /**
     * Parse user.
     *
     * @return $this
     */
    protected function parseUser()
    {
        $this->user = $this->getSourceYamlFromSite("users/{$this->handle}.yaml");

        return $this;
    }

    /**
     * Validate email is present on user to be used as new handle.
     *
     * @throws EmailRequiredException
     * @return $this
     */
    protected function validateEmail()
    {
        if (Validator::make($this->user, ['email' => 'required|email'])->fails()) {
            throw new InvalidEmailException("A valid email is required to migrate user [{$this->handle}].");
        }

        return $this;
    }

    /**
     * Set new path to be used with new email handle.
     *
     * @return $this
     */
    protected function setNewPathWithEmailAsHandle()
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
}
