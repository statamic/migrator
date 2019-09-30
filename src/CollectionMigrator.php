<?php

namespace Statamic\Migrator;

use Statamic\Migrator\YAML;

class CollectionMigrator extends Migrator
{
    use Concerns\MigratesFolder,
        Concerns\MigratesRoute;

    protected $handle;

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
            ->copySourceFiles($handle)
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
