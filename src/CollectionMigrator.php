<?php

namespace Statamic\Migrator;

use Statamic\Migrator\YAML;

class CollectionMigrator extends Migrator
{
    use Concerns\MigratesRoute;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(base_path($relativePath = "content/collections/{$this->handle}"))
            ->validateUnique()
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
        $config = collect(YAML::parse($this->files->get($this->newPath('folder.yaml'))));

        $config->put('blueprints', [$config->get('fieldset')]);
        $config->forget('fieldset');

        $config->put('route', $this->migrateRoute("collections.{$this->handle}", "/{$this->handle}/{slug}"));

        $this->files->put($this->newPath("../{$this->handle}.yaml"), YAML::dump($config->all()));
        $this->files->delete($this->newPath('folder.yaml'));

        return $this;
    }
}
