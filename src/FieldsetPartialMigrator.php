<?php

namespace Statamic\Migrator;

class FieldsetPartialMigrator extends FieldsetMigrator
{
    protected $blueprint;

    /**
     * Save migrated schema.
     *
     * @return $this
     */
    protected function saveMigratedSchema()
    {
        return $this
            ->createBlueprintWrapper()
            ->saveMigratedYaml($this->schema, resource_path("fieldsets/{$this->handle}.yaml"))
            ->saveMigratedYaml($this->blueprint, resource_path("blueprints/{$this->handle}.yaml"));
    }

    /**
     * Create blueprint wrapper, in case partial fieldset is being used as a top-level fieldset.
     *
     * @return $this
     */
    protected function createBlueprintWrapper()
    {
        $this->blueprint = collect($this->schema)
            ->forget('sections')
            ->put('fields', [
                [
                    'import' => $this->handle
                ]
            ])
            ->all();

        return $this;
    }
}
