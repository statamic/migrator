<?php

namespace Statamic\Migrator;

use Statamic\Facades\YAML;

class CollectionMigrator extends Migrator
{
    /**
     * Migrate file.
     *
     * @param string $handle
     */
    public function migrate($handle)
    {
        $this->handle = $handle;
        $this->newPath = base_path("content/collections/{$handle}");

        $this
            ->validateUnique()
            ->migrateYamlConfig();
    }

    /**
     * Migrate yaml config.
     *
     * @return $this
     */
    protected function migrateYamlConfig()
    {
        $config = collect(YAML::parse($this->files->get($this->newPath('folder.yaml'))));

        $config->put('blueprints', [$config->get('fieldset')]);
        $config->forget('fieldset');

        $this->files->put($this->newPath("../{$this->handle}.yaml"), YAML::dump($config));
        $this->files->delete($this->newPath('folder.yaml'));

        return $this;
    }
}
