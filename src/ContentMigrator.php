<?php

namespace Statamic\Migrator;

use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\YAML;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class ContentMigrator
{
    protected $fieldset;
    protected $fieldConfigs;

    /**
     * Instantiate content migrator.
     *
     * @param string $fieldset
     */
    public function __construct(string $fieldset)
    {
        $this->fieldset = $fieldset;

        $this->files = app(Filesystem::class);
    }

    /**
     * Instantiate content migrator.
     *
     * @param string $fieldset
     * @return static
     */
    public static function usingFieldset(string $fieldset)
    {
        return new static($fieldset);
    }

    /**
     * Migrate content.
     *
     * @param array $content
     * @return array
     */
    public function migrateContent($content)
    {
        $this->content = $content;

        $this
            ->getFieldConfigs()
            ->migrateFields()
            ->migrateFieldsetToBlueprint();

        return $this->content;
    }

    /**
     * Get flattened field configs from fieldset.
     *
     * @param string|null $fieldsetPartialHandle
     * @return $this
     */
    protected function getFieldConfigs($fieldsetPartialHandle = null)
    {
        $fieldsetHandle = $fieldsetPartialHandle ?? $this->fieldset;
        $fieldsetPath = $this->sitePath("settings/fieldsets/{$fieldsetHandle}.yaml");

        if ($fieldsetHandle !== 'default' && ! $this->files->exists($fieldsetPath)) {
            // TODO: throw exception and/or handle warning?
            // throw new \Exception("Cannot find fieldset [{$this->fieldset}]");
        }

        $fieldset = $this->files->exists($fieldsetPath)
            ? YAML::parse($this->files->get($fieldsetPath))
            : [];

        $topLevelFields = Arr::get($fieldset, 'fields', []);

        $fieldsInSections = collect(Arr::get($fieldset, 'sections', []))
            ->flatMap(function ($section) {
                return $section['fields'] ?? [];
            })
            ->all();

        $fieldConfigs = $this->ensurePartialsAreImported(array_merge($topLevelFields, $fieldsInSections));

        if ($fieldsetPartialHandle) {
            return $fieldConfigs;
        }

        $this->fieldConfigs = $fieldConfigs;

        return $this;
    }

    /**
     * Ensure partials are imported.
     *
     * @param array $fieldConfigs
     * @return array
     */
    protected function ensurePartialsAreImported($fieldConfigs)
    {
        $flattened = Arr::dot($fieldConfigs);

        collect($flattened)
            ->filter(function ($value, $key) {
                return preg_match('/.*type$/', $key) && $value === 'partial';
            })
            ->map(function ($value, $key) {
                return preg_replace('/(.*)\.type$/', '$1.fieldset', $key);
            })
            ->each(function ($key) use (&$fieldConfigs) {
                $fieldConfigs = $this->importPartial(
                    Arr::get($fieldConfigs, $key),
                    $fieldConfigs,
                    preg_replace('/(.*)\.fieldset$/', '$1', $key)
                );
            });

        return $fieldConfigs;
    }

    /**
     * Import partial.
     *
     * @param string $partialHandle
     * @param array $fieldConfigs
     * @param string $originalKey
     * @return array
     */
    protected function importPartial($partialHandle, $fieldConfigs, $originalKey)
    {
        $partialFieldConfigs = $this->getFieldConfigs($partialHandle);

        Arr::forget($fieldConfigs, $originalKey);

        if (! Str::contains($originalKey, '.')) {
            return array_merge($fieldConfigs, $partialFieldConfigs);
        }

        $keyAtPartialLevel = preg_replace('/(.*)\..*$/', "$1", $originalKey);
        $configsAtPartialLevel = Arr::get($fieldConfigs, $keyAtPartialLevel, []);
        $configsAtPartialLevel = array_merge($configsAtPartialLevel, $partialFieldConfigs);

        Arr::set($fieldConfigs, $keyAtPartialLevel, $configsAtPartialLevel);

        return $fieldConfigs;
    }

    /**
     * Migrate fields.
     *
     * @return $this
     */
    protected function migrateFields()
    {
        foreach ($this->content as $handle => $value) {
            $this->content[$handle] = $this->migrateField($handle, $value);
        }

        return $this;
    }

    /**
     * Migrate field.
     *
     * @param string $handle
     * @param mixed $value
     * @return mixed
     */
    protected function migrateField($handle, $value, $config = null)
    {
        $config = $config ?? $this->getFieldConfig($handle);
        $fieldtype = $this->getFieldtype($config);

        $migrateMethod = 'migrate' . ucfirst(strtolower($fieldtype)) . 'Field';

        if (method_exists($this, $migrateMethod)) {
            return $this->{$migrateMethod}($handle, $value, $config);
        }

        return $value;
    }

    /**
     * Migrate assets field.
     *
     * @param string $handle
     * @param mixed $value
     * @param array $config
     * @return mixed
     */
    protected function migrateAssetsField($handle, $value, $config)
    {
        $containerHandle = $config['container'];
        $container = YAML::parse($this->files->get($this->sitePath("content/assets/{$containerHandle}.yaml")));

        $url = $container['url'];
        $url = Str::ensureLeft($url, '/');
        $url = Str::ensureRight($url, '/');

        if (is_string($value)) {
            return str_replace($url, '', $value);
        }

        return collect($value)->map(function ($asset) use ($url) {
            return str_replace($url, '', $asset);
        })->all();
    }

    /**
     * Migrate replicator field.
     *
     * @param string $handle
     * @param mixed $value
     * @param array $config
     * @return mixed
     */
    protected function migrateReplicatorField($handle, $value, $config)
    {
        $fieldConfigs = collect($config['sets'] ?? [])->map(function ($set) {
            return $set['fields'] ?? [];
        })->all();

        return collect($value)->map(function ($set) use ($fieldConfigs) {
            return $this->migrateReplicatorSet($set, $fieldConfigs);
        })->all();
    }

    /**
     * Migrate replicator set.
     *
     * @param array $set
     * @param array $fieldConfigs
     * @return array
     */
    protected function migrateReplicatorSet($set, $fieldConfigs)
    {
        $setHandle = $set['type'];

        return collect($set)->map(function ($fieldValue, $fieldHandle) use ($setHandle, $fieldConfigs) {
            return $this->migrateField($fieldHandle, $fieldValue, Arr::get($fieldConfigs, "{$setHandle}.{$fieldHandle}", []));
        })->all();
    }

    /**
     * Migrate grid field.
     *
     * @param string $handle
     * @param mixed $value
     * @param array $config
     * @return mixed
     */
    protected function migrateGridField($handle, $value, $config)
    {
        $fieldConfigs = $config['fields'] ?? [];

        return collect($value)->map(function ($row) use ($fieldConfigs) {
            return $this->migrateGridRow($row, $fieldConfigs);
        })->all();
    }

    /**
     * Migrate grid row.
     *
     * @param array $row
     * @param array $fieldConfigs
     * @return array
     */
    protected function migrateGridRow($row, $fieldConfigs)
    {
        return collect($row)->map(function ($fieldValue, $fieldHandle) use ($fieldConfigs) {
            return $this->migrateField($fieldHandle, $fieldValue, Arr::get($fieldConfigs, $fieldHandle, []));
        })->all();
    }

    /**
     * Migrate fieldset to blueprint.
     *
     * @return $this
     */
    protected function migrateFieldsetToBlueprint()
    {
        if (isset($this->content['fieldset'])) {
            $this->content['blueprint'] = $this->content['fieldset'];
        }

        unset($this->content['fieldset']);

        return $this;
    }

    /**
     * Get site path.
     *
     * @param string|null $append
     * @return string
     */
    protected function sitePath($append = null)
    {
        return collect([base_path('site'), $append])->filter()->implode('/');
    }

    /**
     * Get field config.
     *
     * @param string $handle
     */
    protected function getFieldConfig($handle)
    {
        return $this->fieldConfigs[$handle] ?? [];
    }

    /**
     * Get fieldtype from config.
     *
     * @param array $config
     */
    protected function getFieldtype($config)
    {
        return $config['type'] ?? 'text';
    }
}
