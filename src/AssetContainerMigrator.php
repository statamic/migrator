<?php

namespace Statamic\Migrator;

use Statamic\Migrator\YAML;

class AssetContainerMigrator extends Migrator
{
    use Concerns\MigratesFolder;

    protected $handle;

    /**
     * Migrate file.
     *
     * @param string $handle
     */
    public function migrate($handle)
    {
        $this->handle = $handle;
        $this->newPath = base_path('content/assets');

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
        $config = collect(YAML::parse($this->files->get($path = $this->newPath("{$this->handle}.yaml"))));

        $config->put('disk', $config->get('path'));
        $config->forget('path');

        $this->files->put($path, YAML::dump($config->only('title', 'disk')->all()));

        return $this;
    }
}
