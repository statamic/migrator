<?php

namespace Statamic\Migrator;

class FormMigrator extends Migrator
{
    use Concerns\MigratesFile;

    protected $form;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(resource_path($relativePath = "forms/{$this->handle}.yaml"))
            ->validateUnique()
            ->parseForm($relativePath)
            ->migrateFormSchema()
            ->saveMigratedYaml($this->form);
    }

    /**
     * Parse user.
     *
     * @param string $relativePath
     * @return $this
     */
    protected function parseForm($relativePath)
    {
        $this->form = $this->getSourceYaml($relativePath);

        return $this;
    }

    /**
     * Migrate default v2 form schema to default v3 schema.
     *
     * @return $this
     */
    protected function migrateFormSchema()
    {
        $form = collect($this->form);

        return $this;
    }
}
