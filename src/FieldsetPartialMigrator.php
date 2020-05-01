<?php

namespace Statamic\Migrator;

class FieldsetPartialMigrator extends FieldsetMigrator
{
    protected $blueprint;

    /**
     * Specify unique paths that shouldn't be overwritten.
     *
     * @return array
     */
    protected function uniquePaths()
    {
        return [
            resource_path("fieldsets/{$this->handle}.yaml"),
            resource_path("blueprints/{$this->handle}.yaml"),
        ];
    }

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

    /**
     * Migrate v2 fieldset schema to v3 fieldset schema.
     *
     * @return $this
     */
    protected function migrateSchema()
    {
        if (! isset($this->schema['sections'])) {
            return parent::migrateSchema();
        }

        $flattenedFields = collect($this->schema['sections'])->flatMap(function ($section) {
            return $section['fields'] ?? [];
        })->all();

        $this->schema['fields'] = $this->migrateFields($flattenedFields);

        unset($this->schema['sections']);

        return $this;
    }
}
