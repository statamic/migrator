<?php

namespace Statamic\Migrator;

use Statamic\Migrator\YAML;

class AssetContainerMigrator extends Migrator
{
    use Concerns\MigratesFile;

    protected $container;

    /**
     * Perform migration.
     *
     * @param string $handle
     */
    public function migrate()
    {
        $this
            ->setNewPath(base_path($relativePath = "content/assets/{$this->handle}.yaml"))
            ->validateUnique()
            ->parseAssetContainer($relativePath)
            ->migrateYamlConfig()
            ->saveMigratedYaml($this->container);
    }

    /**
     * Parse asset container.
     *
     * @param string $relativePath
     * @return $this
     */
    protected function parseAssetContainer($relativePath)
    {
        $this->container = $this->getSourceYaml($relativePath);

        return $this;
    }

    /**
     * Migrate yaml config.
     *
     * @return $this
     */
    protected function migrateYamlConfig()
    {
        $config = collect($this->container);

        $config->put('disk', $this->migrateDisk());

        $this->container = $config->only('title', 'disk')->all();

        return $this;
    }

    /**
     * Migrate disk.
     *
     * @return string
     */
    protected function migrateDisk()
    {
        return 'assets';
    }
}
