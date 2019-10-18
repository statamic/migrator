<?php

namespace Statamic\Migrator\Concerns;

trait DirectlyModifiesFilesystemConfig
{
    /**
     * Attempt to insert the disk config in a pretty way.
     *
     * @param string $diskConfig
     * @return bool
     */
    protected function attemptGracefulDiskInsertion($diskConfig)
    {
        $config = $this->files->get($configPath = config_path('filesystems.php'));

        preg_match($regex = '/(\X*\s{4}[\'"]disks\X*\s{8})\],*\s*\n*(^\s{4}\])/mU', $config, $matches);

        if (count($matches) != 3) {
            return false;
        }

        $updatedConfig = preg_replace($regex, "$1],\n\n{$diskConfig}\n\n$2", $config);

        $this->files->put($configPath, $updatedConfig);

        return true;
    }

    /**
     * Insert the disk config, without really caring how it looks.
     *
     * @param string $diskConfig
     * @return bool
     */
    protected function jamDiskIntoDrive($diskConfig)
    {
        $config = $this->files->get($configPath = config_path('filesystems.php'));

        preg_match($regex = '/([\'"]disks[\'"].*$)/mU', $config, $matches);

        if (count($matches) != 2) {
            return false;
        }

        $updatedConfig = preg_replace($regex, "$1\n{$diskConfig}\n", $config);

        $this->files->put($configPath, $updatedConfig);

        return true;
    }

    /**
     * Replace filesystem disk.
     *
     * @param string $disk
     * @param string $diskConfig
     * @return bool
     */
    protected function replaceFilesystemDisk($disk, $diskConfig)
    {
        $config = $this->files->get($configPath = config_path('filesystems.php'));

        preg_match($regex = "/^(\s{8}['\"]{$this->disk}['\"]\X*^\s{8}],)$/mU", $config, $matches);

        if (count($matches) != 2) {
            return false;
        }

        $updatedConfig = preg_replace($regex, $diskConfig, $config);

        $this->files->put($configPath, $updatedConfig);

        return true;
    }

    /**
     * Refresh filesystems config, since we manually injected new config directly into the PHP file.
     *
     * @return $this
     */
    protected function refreshFilesystems()
    {
        $updatedFilesystemsConfig = include config_path('filesystems.php');

        config(['filesystems' => $updatedFilesystemsConfig]);

        return $this;
    }
}
