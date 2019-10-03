<?php

namespace Statamic\Migrator;

use Statamic\Migrator\YAML;

class AssetContainerMigrator extends Migrator
{
    /**
     * Perform migration.
     *
     * @param string $handle
     */
    public function migrate()
    {
        $this
            ->setNewPath(base_path($relativePath = 'content/assets'))
            // ->validateUnique()
            ->copyDirectoryFromSiteToNewPath($relativePath)
            ->migrateYamlConfig();
    }

    /**
     * Migrate yaml config.
     *
     * @return $this
     */
    protected function migrateYamlConfig()
    {
        $config = collect(YAML::parse($this->files->get($path = $this->newPath("{$this->handle}.yaml"))));

        $config->put('disk', $config->get('path'));
        $config->forget('path');

        $this->files->put($path, YAML::dump($config->only('title', 'disk')->all()));

        return $this;
    }
}
