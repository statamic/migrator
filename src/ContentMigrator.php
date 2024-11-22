<?php

namespace Statamic\Migrator;

use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\Exceptions\EmptyValueException;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class ContentMigrator
{
    use Concerns\GetsSettings;

    protected $fieldset;
    protected $addExplicitBlueprint = true;
    protected $fieldConfigs;

    /**
     * Instantiate content migrator.
     *
     * @param  string  $fieldset
     */
    public function __construct(string $fieldset)
    {
        $this->fieldset = $fieldset;

        $this->files = app(Filesystem::class);
    }

    /**
     * Instantiate content migrator.
     *
     * @param  string  $fieldset
     * @return static
     */
    public static function usingFieldset(string $fieldset)
    {
        return new static($fieldset);
    }

    /**
     * Add explicit blueprint.
     *
     * @param  bool  $addExplicitBlueprint
     * @return $this
     */
    public function addExplicitBlueprint($addExplicitBlueprint = true)
    {
        $this->addExplicitBlueprint = $addExplicitBlueprint;

        return $this;
    }

    /**
     * Migrate content.
     *
     * @param  array  $content
     * @return array
     */
    public function migrateContent($content)
    {
        $this->content = $content;

        $this
            ->getFieldConfigs()
            ->migrateFields()
            ->migrateFieldsetToBlueprint()
            ->migrateLayout();

        return $this->content;
    }

    /**
     * Get flattened field configs from fieldset.
     *
     * @param  string|null  $fieldsetPartialHandle
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
     * @param  array  $fieldConfigs
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
     * @param  string  $partialHandle
     * @param  array  $fieldConfigs
     * @param  string  $originalKey
     * @return array
     */
    protected function importPartial($partialHandle, $fieldConfigs, $originalKey)
    {
        if (is_null($partialHandle)) {
            return $fieldConfigs;
        }

        $partialFieldConfigs = $this->getFieldConfigs($partialHandle);

        Arr::forget($fieldConfigs, $originalKey);

        if (! Str::contains($originalKey, '.')) {
            return array_merge($fieldConfigs, $partialFieldConfigs);
        }

        $keyAtPartialLevel = preg_replace('/(.*)\..*$/', '$1', $originalKey);
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
        foreach ($this->content ?: [] as $handle => $value) {
            try {
                $this->content[$handle] = $this->migrateField($handle, $value);
            } catch (EmptyValueException $exception) {
                unset($this->content[$handle]);
            }
        }

        return $this;
    }

    /**
     * Migrate field.
     *
     * @param  string  $handle
     * @param  mixed  $value
     * @return mixed
     */
    protected function migrateField($handle, $value, $config = null)
    {
        $config = $config ?? $this->getFieldConfig($handle);
        $fieldtype = $this->getFieldtype($config);

        $migrateMethod = 'migrate'.str($fieldtype)->studly().'Field';

        if (method_exists($this, $migrateMethod)) {
            return $this->{$migrateMethod}($handle, $value, $config);
        }

        return $value;
    }

    /**
     * Migrate assets field.
     *
     * @param  string  $handle
     * @param  mixed  $value
     * @param  array  $config
     * @return mixed
     */
    protected function migrateAssetsField($handle, $value, $config)
    {
        $containerHandle = $config['container'];
        $container = YAML::parse($this->files->get($this->sitePath("content/assets/{$containerHandle}.yaml")));

        $url = $container['url'] ?? null;
        $url = Str::ensureLeft($url, '/');
        $url = Str::ensureRight($url, '/');
        $url = preg_quote($url, '/');

        if (is_string($value)) {
            return preg_replace("/^$url(.*)/", '$1', $value);
        }

        return collect($value)->map(function ($asset) use ($url) {
            return preg_replace("/^$url(.*)/", '$1', $asset);
        })->all();
    }

    /**
     * Migrate taxonomy field.
     *
     * @param  string  $handle
     * @param  mixed  $value
     * @param  array  $config
     * @return mixed
     */
    protected function migrateTaxonomyField($handle, $value, $config)
    {
        $shouldReturnSingle = Arr::get($config, 'max_items', null) == 1;

        $values = collect($value)->map(function ($term) use ($handle, $config) {
            return $this->migrateTermValue($term, $handle, $config);
        })->filter()->values();

        if ($values->isEmpty()) {
            throw new EmptyValueException;
        }

        return $shouldReturnSingle
            ? $values->first()
            : $values->all();
    }

    /**
     * Migrate term value.
     *
     * @param  string  $termValue
     * @param  string  $fieldHandle
     * @param  array  $fieldConfig
     * @return string
     */
    protected function migrateTermValue($termValue, $fieldHandle, $fieldConfig)
    {
        $taxonomies = collect(Arr::get($fieldConfig, 'taxonomy'));

        if ($taxonomies->count() === 1 && $taxonomies->first() === $fieldHandle) {
            return $termValue;
        }

        return $taxonomies->count() > 1
            ? str_replace('/', '::', $termValue)
            : preg_replace('/[^\/]*\/(.*)/', '$1', $termValue);
    }

    /**
     * Migrate suggest field.
     *
     * @param  string  $handle
     * @param  mixed  $value
     * @param  array  $config
     * @return mixed
     */
    protected function migrateSuggestField($handle, $value, $config)
    {
        if (Arr::get($config, 'mode') === 'taxonomy') {
            return $this->migrateTaxonomyField($handle, $value, $config);
        }

        return $value;
    }

    /**
     * Migrate replicator field.
     *
     * @param  string  $handle
     * @param  mixed  $value
     * @param  array  $config
     * @return mixed
     */
    protected function migrateReplicatorField($handle, $value, $config)
    {
        if (! isset($config['sets'])) {
            return $value;
        }

        $fieldConfigs = collect($config['sets'])->map(function ($set) {
            return $set['fields'] ?? [];
        })->all();

        return collect($value)->map(function ($set) use ($fieldConfigs) {
            return $this->migrateReplicatorSet($set, $fieldConfigs);
        })->all();
    }

    /**
     * Migrate replicator set.
     *
     * @param  array  $set
     * @param  array  $fieldConfigs
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
     * Migrate bard field.
     *
     * @param  string  $handle
     * @param  mixed  $value
     * @param  array  $config
     * @return mixed
     */
    protected function migrateBardField($handle, $value, $config)
    {
        // If content was saved while bard had no sets, value may be a string, so we'll normalize to a bard text set...
        if (is_string($value)) {
            $value = [
                ['type' => 'text', 'text' => $value],
            ];
        }

        return $this->migrateReplicatorField($handle, $value, $config);
    }

    /**
     * Migrate grid field.
     *
     * @param  string  $handle
     * @param  mixed  $value
     * @param  array  $config
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
     * @param  array  $row
     * @param  array  $fieldConfigs
     * @return array
     */
    protected function migrateGridRow($row, $fieldConfigs)
    {
        return collect($row)->map(function ($fieldValue, $fieldHandle) use ($fieldConfigs) {
            return $this->migrateField($fieldHandle, $fieldValue, Arr::get($fieldConfigs, $fieldHandle, []));
        })->all();
    }

    protected function migrateLinkitField($handle, $value, $config)
    {
        if ($value['type'] === 'asset') {
            // Use Statamic's migration logic which handles URLs, but they are
            // returned without container id prefixes, and LinkIt expects them.
            $asset = $this->migrateAssetsField(null, $value['asset'], ['container' => $value['container']]);
            $value['asset'] = collect($asset)->map(fn ($a) => $value['container'].'::'.$a)->all();
        }

        if ($value['type'] === 'term') {
            $value['term'] = collect($value['term'])->map(fn ($t) => str($t)->replace('/', '::')->toString())->all();
        }

        if ($value['type'] === 'page') {
            $value['type'] = 'entry';
            $value['entry'] = Arr::pull($value, 'page');
        }

        return $value;
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
        } elseif ($this->addExplicitBlueprint && $this->fieldsetExists()) {
            $this->content['blueprint'] = $this->fieldset;
        }

        unset($this->content['fieldset']);

        return $this;
    }

    /**
     * Migrate layout.
     *
     * @return $this
     */
    protected function migrateLayout()
    {
        $defaultLayout = $this->getSetting('theming.default_layout', 'default');

        if (isset($this->content['layout']) && $this->content['layout'] == $defaultLayout) {
            unset($this->content['layout']);
        }

        return $this;
    }

    /**
     * Get site path.
     *
     * @param  string|null  $append
     * @return string
     */
    protected function sitePath($append = null)
    {
        return collect([base_path('site'), $append])->filter()->implode('/');
    }

    /**
     * Get field config.
     *
     * @param  string  $handle
     */
    protected function getFieldConfig($handle)
    {
        return $this->fieldConfigs[$handle] ?? [];
    }

    /**
     * Get fieldtype from config.
     *
     * @param  array  $config
     */
    protected function getFieldtype($config)
    {
        return $config['type'] ?? 'text';
    }

    /**
     * Fieldset exists.
     *
     * @return bool
     */
    protected function fieldsetExists()
    {
        return $this->files->exists($this->sitePath("settings/fieldsets/{$this->fieldset}.yaml"));
    }
}
