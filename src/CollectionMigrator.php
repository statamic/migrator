<?php

namespace Statamic\Migrator;

use Statamic\Migrator\YAML;

class CollectionMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\MigratesRoute;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(base_path($relativePath = "content/collections/{$this->handle}"))
            ->validateUnique()
            ->copyDirectoryFromSiteToNewPath($relativePath)
            ->migrateYamlConfig()
            ->deleteOldConfig();
    }

    /**
     * Migrate yaml config.
     *
     * @return $this
     */
    protected function migrateYamlConfig()
    {
        $config = $this->getSourceYaml("content/collections/{$this->handle}/folder.yaml", true);

        if ($fieldset = $config->get('fieldset')) {
            $config->put('blueprints', [$fieldset]);
            $config->forget('fieldset');
        }

        if ($route = $this->migrateRoute("collections.{$this->handle}")) {
            $config->put('route', $route);
        }

        $this->saveMigratedYaml($config, $this->newPath("../{$this->handle}.yaml"));

        return $this;
    }

    /**
     * Delete old folder.yaml config from copied folder.
     *
     * @return $this
     */
    protected function deleteOldConfig()
    {
        $this->files->delete($this->newPath('folder.yaml'));

        return $this;
    }
}
