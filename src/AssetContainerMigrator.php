<?php

namespace Statamic\Migrator;

use Statamic\Migrator\YAML;
use Statamic\Migrator\Exceptions\AlreadyExistsException;

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
        $disk = $this->migrateDiskKey();

        if (config()->has($configKey = "filesystems.disks.{$disk}")) {
            throw new AlreadyExistsException("Asset container filesystem disk [{$disk}] already exists.");
        }

        if ($this->attemptGracefulDiskInsertion($disk)) {
            return $disk;
        } elseif ($this->jamDiskIntoDrive($disk)) {
            return $disk;
        }

        throw new \Exception('Cannot migrate filesystem config');
    }

    /**
     * Migrate disk key.
     *
     * @return string
     */
    protected function migrateDiskKey()
    {
        return config('filesystems.disks.assets') || count($this->files->files($this->sitePath('content/assets'))) > 1
            ? "assets_{$this->handle}"
            : 'assets';
    }

    /**
     * Attempt to insert the disk config in a pretty way.
     *
     * @param string $disk
     * @return bool
     */
    protected function attemptGracefulDiskInsertion($disk)
    {
        $config = $this->files->get($configPath = config_path('filesystems.php'));

        preg_match($regex = '/(\X*\s{4}[\'"]disks\X*\s{8})\],*\s*\n*(^\s{4}\])/mU', $config, $matches);

        if (count($matches) != 3) {
            return false;
        }

        $updatedConfig = preg_replace($regex, '$1],' . $this->containerDiskConfig($disk) . '$2', $config);

        $this->files->put($configPath, $updatedConfig);

        return true;
    }

    /**
     * Insert the disk config, without really caring how it looks.
     *
     * @param string $disk
     * @return bool
     */
    protected function jamDiskIntoDrive($disk)
    {
        $config = $this->files->get($configPath = config_path('filesystems.php'));

        preg_match($regex = '/([\'"]disks[\'"].*$)/mU', $config, $matches);

        if (count($matches) != 2) {
            return false;
        }

        $updatedConfig = preg_replace($regex, '$1' . $this->containerDiskConfig($disk), $config);

        $this->files->put($configPath, $updatedConfig);

        return true;
    }

    /**
     * Generate container disk config.
     *
     * @param string $disk
     * @return string
     */
    protected function containerDiskConfig($disk)
    {
        return <<<EOT
\n
        '{$disk}' => [
            'driver' => 'local',
            'root' => public_path('assets'),
            'url' => '/assets',
            'visibility' => 'public',
        ],
\n
EOT;
    }
}
