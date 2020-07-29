<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Support\Str;

/**
 * Temporary, until we implement sections in fieldsets!
 */
trait MigratesFlattenedFieldsetSchema
{
    protected function shouldBeFlattened()
    {
        return ! Str::contains($this->newPath(), 'resources/blueprints');
    }

    protected function flattenSections()
    {
        $flattenedFields = collect($this->schema['sections'])->flatMap(function ($section) {
            return $section['fields'] ?? [];
        })->all();

        $this->schema['fields'] = $this->migrateFields($flattenedFields);

        unset($this->schema['sections']);

        return $this;
    }
}
