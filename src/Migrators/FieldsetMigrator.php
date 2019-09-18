<?php

namespace Statamic\Migrator\Migrators;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Migrator\Exceptions\AlreadyExistsException;

class FieldsetMigrator extends Migrator
{
    /**
     * Migrate file.
     *
     * @param string $handle
     */
    public function migrate($handle)
    {
        $newPath = resource_path("blueprints/{$handle}.yaml");

        if ($this->files->exists($newPath)) {
            throw new AlreadyExistsException;
        }

        $fieldset = $this->getSourceYaml($handle);
        $blueprint = $this->migrateFieldsetToBlueprint($fieldset);

        $this->saveMigratedToYaml($newPath, $blueprint);
    }

    /**
     * Migrate fieldset contents to blueprint.
     *
     * @param string $fieldset
     * @return string
     */
    protected function migrateFieldsetToBlueprint($fieldset)
    {
        $migrated = $fieldset;

        if (isset($migrated['fields'])) {
            $migrated['fields'] = $this->migrateFields($migrated['fields']);
        }

        if (isset($migrated['sections'])) {
            $migrated['sections'] = $this->migrateSections($migrated['sections']);
        }

        return $migrated;
    }

    /**
     * Migrate sections.
     *
     * @param array $sections
     * @return array
     */
    protected function migrateSections($sections)
    {
        return collect($sections)
            ->map(function ($section) {
                return Arr::set($section, 'fields', $this->migrateFields($section['fields']));
            })
            ->all();
    }

    /**
     * Migrate fields.
     *
     * @param array $fields
     * @return array
     */
    protected function migrateFields($fields)
    {
        return collect($fields)
            ->map(function ($field, $handle) {
                return $this->migrateField($field, $handle);
            })
            ->values()
            ->all();
    }

    /**
     * Migrate field.
     *
     * @param array $field
     * @param string $handle
     * @return array
     */
    protected function migrateField($field, $handle)
    {
        return [
            'handle' => $field['handle'] ?? $handle,
            'field' => $field['field'] ?? $this->migrateFieldConfig($field),
        ];
    }

    /**
     * Migrate field config.
     *
     * @param array $config
     * @return array
     */
    protected function migrateFieldConfig($config)
    {
        $config = collect($config)->except(['handle', 'field']);

        if ($fields = $config['fields'] ?? false) {
            $config->put('fields', $this->migrateFields($fields));
        }

        if ($sets = $config['sets'] ?? false) {
            $config->put('sets', $this->migrateSets($sets));
        }

        if ($config['show_when'] ?? $config['hide_when'] ?? false) {
            $config = $this->migrateFieldConditions($config);
        }

        return $this->normalizeConfigToArray($config);
    }

    /**
     * Migrate replicator/bard/etc sets.
     *
     * @param array $sets
     * @return array
     */
    protected function migrateSets($sets)
    {
        return collect($sets)
            ->map(function ($set) {
                return collect($set)->put('fields', $this->migrateFields($set['fields']))->all();
            })
            ->all();
    }

    /**
     * Migrate field conditions.
     *
     * @param \Illuminate\Support\Collection $config
     * @return \Illuminate\Support\Collection
     */
    public function migrateFieldConditions($config)
    {
        $key = $config->has('hide_when') ? 'hide_when' : 'show_when';

        if (is_string($config->get($key))) {
            return $config;
        }

        $conditions = collect($config->get($key))
            ->each(function ($condition, $field) use (&$key) {
                $key = Str::startsWith($field, 'or_') ? "{$key}_any" : $key;
            })
            ->mapWithKeys(function ($condition, $field) {
                return [preg_replace('/^or_/', '', $field) => $condition];
            })
            ->map(function ($condition) {
                return str_replace('not null', 'not empty', Str::lower($condition));
            })
            ->all();

        return $config
            ->forget('show_when')
            ->forget('hide_when')
            ->put($key, $conditions);
    }

    /**
     * Normalize config and cast back to array.
     *
     * @param \Illuminate\Support\Collection $config
     * @return array
     */
    protected function normalizeConfigToArray($config)
    {
        return $config
            ->except('type')
            ->prepend(Arr::get($config, 'type', 'text'), 'type')
            ->all();
    }
}
