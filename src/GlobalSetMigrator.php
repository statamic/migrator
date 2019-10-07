<?php

namespace Statamic\Migrator;

class GlobalSetMigrator extends Migrator
{
    use Concerns\MigratesFile;

    protected $set;

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
            ->saveMigratedYaml($this->set);
    }

    /**
     * Parse user.
     *
     * @return $this
     */
    protected function parseGlobalSet()
    {
        $this->set = $this->getSourceYaml($relativePath);

        return $this;
    }

    /**
     * Migrate default v2 global set schema to default v3 schema.
     *
     * @return $this
     */
    protected function migrateGlobalSetSchema()
    {
        $set = collect($this->set);

        $set->put('blueprint', $set->get('fieldset', 'global'));
        $set->forget('fieldset');

        $nonData = $set->except(['id', 'blueprint', 'title']);

        $set->put('data', $nonData);

        $this->set = $set->intersectByKeys($nonData)->all();

        return $this;
    }
}
