<?php

namespace Statamic\Migrator;

class GlobalSetMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\MigratesContent,
        Concerns\MigratesLocalizedContent,
        Concerns\MigratesFieldsetsToBlueprints,
        Concerns\ThrowsFinalWarnings;

    protected $set;
    protected $localizedSets;
    protected $fieldset;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(base_path($relativePath = "content/globals/{$this->handle}.yaml"))
            ->validateUnique()
            ->parseGlobalSet($relativePath)
            ->migrateGlobalSetSchema()
            ->saveMigratedSet()
            ->migrateFieldset()
            ->throwFinalWarnings();
    }

    /**
     * Parse global set.
     *
     * @param  string  $relativePath
     * @return $this
     */
    protected function parseGlobalSet($relativePath)
    {
        $this->set = $this->getSourceYaml($relativePath, true);

        $this->fieldset = $this->set->get('fieldset', 'globals');

        return $this;
    }

    /**
     * Migrate default v2 global set schema to new schema.
     *
     * @return $this
     */
    protected function migrateGlobalSetSchema()
    {
        $metaKeys = ['title'];

        $this->set
            ->forget('fieldset')
            ->forget('id');

        $this->localizedSets = $this->migrateLocalizedSets($this->set->except($metaKeys));
        $this->set = $this->set->only($metaKeys);

        return $this;
    }

    /**
     * Migrate localized sets.
     *
     * @param  array  $data
     * @return \Illuminate\Support\Collection
     */
    protected function migrateLocalizedSets($data)
    {
        return $this
            ->getMigratedSiteKeys()
            ->mapWithKeys(function ($site) use ($data) {
                return [$site => $site === 'default' ? $data : $this->migrateLocalizedSet($site)];
            })
            ->map(function ($setData) {
                return $this->migrateSetData($setData);
            })
            ->filter();
    }

    /**
     * Migrate specific localized set.
     *
     * @param  string  $site
     * @return \Illuminate\Support\Collection
     */
    protected function migrateLocalizedSet($site)
    {
        $path = $this->sitePath("content/globals/{$site}/{$this->handle}.yaml");

        if (! $this->files->exists($path)) {
            return false;
        }

        return $this->getSourceYaml($path, true)
            ->forget('id')
            ->put('origin', 'default');
    }

    /**
     * Migrate set data.
     *
     * @param  array  $data
     * @return array
     */
    protected function migrateSetData($data)
    {
        return $this->migrateContent($data, $this->fieldset, false);
    }

    /**
     * Save migrated global set, along with new localized versions.
     *
     * @return $this
     */
    protected function saveMigratedSet()
    {
        collect($this->localizedSets)->each(function ($set, $locale) {
            $this->saveMigratedYaml($set, base_path("content/globals/{$locale}/{$this->handle}.yaml"));
        });

        return $this->saveMigratedYaml($this->set);
    }

    /**
     * Migrate fieldset.
     *
     * @return $this
     */
    protected function migrateFieldset()
    {
        if ($this->fieldset) {
            $this->migrateFieldsetToBlueprint('globals', $this->fieldset, $this->handle);
        }

        return $this;
    }
}
