<?php

namespace Statamic\Migrator;

class GlobalSetMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\MigratesLocalizedContent;

    protected $set;
    protected $localizedSets;

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
            ->saveMigratedSet();
    }

    /**
     * Parse global set.
     *
     * @param string $relativePath
     * @return $this
     */
    protected function parseGlobalSet($relativePath)
    {
        $this->set = $this->getSourceYaml($relativePath, true);

        return $this;
    }

    /**
     * Migrate default v2 global set schema to default v3 schema.
     *
     * @return $this
     */
    protected function migrateGlobalSetSchema()
    {
        $metaKeys = ['blueprint', 'title'];

        $this->set
            ->put('blueprint', $this->set->get('fieldset', 'global'))
            ->forget('fieldset')
            ->forget('id');

        $meta = $this->set->only($metaKeys);
        $data = $this->set->except($metaKeys);

        if ($this->isMultisite()) {
            $this->set = $meta;
            $this->localizedSets = $this->migrateLocalizedSets($data);
        } else {
            $this->set = $meta->put('data', $data);
        }

        return $this;
    }

    /**
     * Migrate localized sets.
     *
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    protected function migrateLocalizedSets($data)
    {
        $sets = ['default' => $data];

        return $this->getMigratedSiteKeys()->mapWithKeys(function ($site) use ($data) {
            return [$site => $site === 'default' ? $data : $this->migrateLocalizedSet($site)];
        });
    }

    /**
     * Migrate specific localized set.
     *
     * @param string $site
     * @return \Illuminate\Support\Collection
     */
    protected function migrateLocalizedSet($site)
    {
        return $this->getSourceYaml($this->sitePath("content/globals/{$site}/{$this->handle}.yaml"), true)
            ->forget('id')
            ->put('origin', 'default');
    }

    /**
     * Save migrated global set, along with new localized versions.
     *
     * @return $this
     */
    protected function saveMigratedSet()
    {
        if ($this->isMultisite()) {
            $this->saveLocalizedSets();
        }

        return $this->saveMigratedYaml($this->set);
    }

    /**
     * Save migrated localized sets.
     *
     * @return $this
     */
    protected function saveLocalizedSets()
    {
        collect($this->localizedSets)->each(function ($set, $locale) {
            $this->saveMigratedYaml($set, base_path("content/globals/{$locale}/{$this->handle}.yaml"));
        });
    }
}
