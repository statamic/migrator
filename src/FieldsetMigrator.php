<?php

namespace Statamic\Migrator;

use Statamic\Support\Arr;
use Statamic\Support\Str;

class FieldsetMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\ThrowsFinalWarnings;

    protected $blueprint;

    /**
     * Perform migration.
     *
     * @param string $handle
     */
    public function migrate()
    {
        $this
            ->parseBlueprint()
            ->setNewPath(resource_path("blueprints/{$this->handle}.yaml"))
            ->validateUnique()
            ->migrateToBlueprintSchema()
            ->removeOldFunctionality()
            ->saveMigratedYaml($this->blueprint)
            ->throwFinalWarnings();
    }

    /**
     * Parse blueprint.
     *
     * @return $this
     */
    protected function parseBlueprint()
    {
        $this->blueprint = $this->getSourceYaml("settings/fieldsets/{$this->handle}.yaml");

        return $this;
    }

    /**
     * Migrate v2 fieldset schema to v3 blueprint schema.
     *
     * @return $this
     */
    protected function migrateToBlueprintSchema()
    {
        if (isset($this->blueprint['fields'])) {
            $this->blueprint['fields'] = $this->migrateFields($this->blueprint['fields']);
        }

        if (isset($this->blueprint['sections'])) {
            $this->blueprint['sections'] = $this->migrateSections($this->blueprint['sections']);
        }

        return $this;
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
                return Arr::set($section, 'fields', $this->migrateFields($section['fields'] ?? []));
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
        $handle = $field['handle'] ?? $handle;
        $field = $field['field'] ?? $this->migrateFieldConfig($field, $handle);

        if ($this->getFieldtype($field) === 'partial') {
            return $this->convertPartialFieldToImport($field);
        }

        return compact('handle', 'field');
    }

    /**
     * Migrate field config.
     *
     * @param array $config
     * @param string $handle
     * @return array
     */
    protected function migrateFieldConfig($config, $handle)
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

        $fieldtype = $config['type'] ?? 'text';

        $migrateMethod = 'migrate' . ucfirst(strtolower($fieldtype)) . 'Field';

        if (method_exists($this, $migrateMethod)) {
            $config = $this->{$migrateMethod}($config, $handle);
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
     * Migrate redactor field.
     *
     * @param \Illuminate\Support\Collection $config
     * @param string $handle
     * @return \Illuminate\Support\Collection
     */
    protected function migrateRedactorField($config, $handle)
    {
        $this->addWarning(
            "Redactor field [{$handle}] has been migrated to bard.",
            "Not all redactor features and settings are bard-compatible.\n" .
            "Please revise your bard configuration as necessary."
        );

        return $config
            ->put('type', 'bard')
            ->put('save_html', true)
            ->put('buttons', $this->migrateRedactorButtons($config))
            ->forget('settings');
    }

    /**
     * Migrate redactor buttons.
     *
     * @param \Illuminate\Support\Collection $config
     * @return array
     */
    protected function migrateRedactorButtons($config)
    {
        $buttons = $this->getRedactorButtons($config);

        if ($config['container'] ?? false) {
            $buttons->push('image');
        }

        if ($buttons->has('formatting')) {
            $buttons = $buttons
                ->merge(['quote', 'codeblock', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'])
                ->forget('formatting');
        }

        if ($buttons->has('link')) {
            $buttons->push('anchor')->forget('link');
        }

        return $buttons->unique()->values()->all();
    }

    /**
     * Get redactor buttons from system config.
     *
     * @param \Illuminate\Support\Collection $config
     * @return \Illuminate\Support\Collection
     */
    protected function getRedactorButtons($config)
    {
        $defaultSettings = [
            ['name' => 'Standard', 'settings' => ['buttons' => ['formatting', 'bold', 'italic', 'link', 'unorderedlist', 'orderedlist', 'html']]],
            ['name' => 'Basic', 'settings' => ['buttons' => ['bold', 'italic']]],
        ];

        $buttons = collect($this->getSourceYaml('settings/system.yaml')['redactor'] ?? $defaultSettings)
            ->keyBy('name')
            ->map(function ($preset) {
                return $preset['settings']['buttons'];
            })
            ->get($config['settings'] ?? 'Standard');

        return collect($buttons)->keyBy(function ($value) {
            return $value;
        });
    }

    /**
     * Remove old fieldset functionality that doesn't apply to blueprints.
     *
     * @return $this
     */
    protected function removeOldFunctionality()
    {
        unset($this->blueprint['hide']);

        return $this;
    }

    /**
     * Migrate pages field.
     *
     * @param \Illuminate\Support\Collection $config
     * @param string $handle
     * @return \Illuminate\Support\Collection
     */
    protected function migratePagesField($config, $handle)
    {
        $this->addWarning(
            "Pages field [{$handle}] has been migrated to an entries field.",
            "Not all config features and settings are compatible.\n" .
            "Please revise your entries field configuration as necessary."
        );

        return $config
            ->put('type', 'entries')
            ->put('collections', ['pages']);
    }

    /**
     * Migrate collection field.
     *
     * @param \Illuminate\Support\Collection $config
     * @param string $handle
     * @return \Illuminate\Support\Collection
     */
    protected function migrateCollectionField($config, $handle)
    {
        $this->addWarning(
            "Collection field [{$handle}] has been migrated to an entries field.",
            "Not all config features and settings are compatible.\n" .
            "Please revise your entries field configuration as necessary."
        );

        return $config
            ->put('type', 'entries')
            ->put('collections', $config->get('collection'))
            ->forget('collection');
    }

    /**
     * Convert partial field to import.
     *
     * @param array $config
     * @return array
     */
    protected function convertPartialFieldToImport($config)
    {
        return [
            'import' => $config['fieldset']
        ];
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
            ->prepend($this->getFieldtype($config), 'type')
            ->all();
    }

    /**
     * Get fieldtype.
     *
     * @param array $config
     * @return string
     */
    protected function getFieldtype($config)
    {
        return Arr::get($config, 'type', 'text');
    }
}
