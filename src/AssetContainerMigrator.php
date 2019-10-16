<?php

namespace Statamic\Migrator;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Migrator\YAML;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Statamic\Migrator\Exceptions\NotFoundException;
use Statamic\Migrator\Exceptions\AlreadyExistsException;
use Statamic\Migrator\Exceptions\InvalidContainerDriverException;

class AssetContainerMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\ThrowsFinalWarnings;

    protected $disk;
    protected $container;
    protected $localPath;
    protected $metaData;

    /**
     * Perform migration.
     *
     * @param string $handle
     */
    public function migrate()
    {
        $this
            ->setNewPath(base_path($relativePath = "content/assets/{$this->handle}.yaml"))
            ->parseDiskKey()
            ->validateUnique()
            ->parseAssetContainer($relativePath)
            ->migrateYamlConfig()
            ->migrateDisk()
            ->migrateFolder()
            ->migrateMeta()
            ->throwFinalWarnings();
    }

    /**
     * Parse disk key.
     *
     * @return string
     */
    protected function parseDiskKey()
    {
        $this->disk = $this->diskExists('assets') || count($this->files->files($this->sitePath('content/assets'))) > 1
            ? 'assets_' . strtolower($this->handle)
            : 'assets';

        return $this;
    }

    /**
     * Validate unique.
     *
     * @throws AlreadyExistsException
     * @return $this
     */
    protected function validateUnique()
    {
        if (config()->has("filesystems.disks.{$this->disk}")) {
            throw new AlreadyExistsException("Asset container filesystem disk [{$this->disk}] already exists.");
        }

        return parent::validateUnique();
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

        $this->driver = $this->parseDriver($config);
        $this->localPath = $this->parseLocalPath($config);
        $this->metaData = $this->parseMeta($config);

        $config->put('disk', $this->disk);

        $this->container = $config->only('title', 'disk')->all();

        return $this->saveMigratedYaml($this->container);
    }

    /**
     * Parse disk driver.
     *
     * @param array $config
     * @return string
     */
    protected function parseDriver($config)
    {
        return strtolower(Arr::get($config, 'driver', 'local'));
    }

    /**
     * Parse local path.
     *
     * @param string $config
     * @return null|string
     */
    protected function parseLocalPath($config)
    {
        if (Arr::get($config, 'driver', 'local') !== 'local') {
            return null;
        }

        $path = Arr::get($config, 'path');

        $path = collect(explode('/', $path))->filter()->last();

        return base_path($path);
    }

    /**
     * Parse meta.
     *
     * @param string $config
     * @return array
     */
    protected function parseMeta($config)
    {
        return collect(Arr::get($config, 'assets', []))
            ->map(function ($metaData) {
                return Arr::only($metaData, ['alt', 'focus']);
            })
            ->filter()
            ->map(function ($metaData) {
                if (isset($metaData['focus'])) {
                    $metaData['focus'] .= '-1';
                }

                return ['data' => $metaData];
            });
    }

    /**
     * Migrate disk.
     *
     * @return $this
     * @throws \Exception
     */
    protected function migrateDisk()
    {
        if ($this->attemptGracefulDiskInsertion() === true) {
            return $this;
        } elseif ($this->jamDiskIntoDrive() === true) {
            return $this;
        }

        throw new \Exception('Cannot migrate filesystem config');
    }

    /**
     * Attempt to insert the disk config in a pretty way.
     *
     * @return bool
     */
    protected function attemptGracefulDiskInsertion()
    {
        $config = $this->files->get($configPath = config_path('filesystems.php'));

        preg_match($regex = '/(\X*\s{4}[\'"]disks\X*\s{8})\],*\s*\n*(^\s{4}\])/mU', $config, $matches);

        if (count($matches) != 3) {
            return false;
        }

        $updatedConfig = preg_replace($regex, '$1],' . $this->diskConfig() . '$2', $config);

        $this->files->put($configPath, $updatedConfig);

        return true;
    }

    /**
     * Insert the disk config, without really caring how it looks.
     *
     * @return bool
     */
    protected function jamDiskIntoDrive()
    {
        $config = $this->files->get($configPath = config_path('filesystems.php'));

        preg_match($regex = '/([\'"]disks[\'"].*$)/mU', $config, $matches);

        if (count($matches) != 2) {
            return false;
        }

        $updatedConfig = preg_replace($regex, '$1' . $this->diskConfig(), $config);

        $this->files->put($configPath, $updatedConfig);

        return true;
    }

    /**
     * Generate container disk config.
     *
     * @return string
     */
    protected function diskConfig()
    {
        switch ($this->driver) {
            case 'local':
                return $this->localDiskConfig();
            case 's3':
                return $this->s3DiskConfig();
        }

        throw new InvalidContainerDriverException("Cannot migrate asset container with [{$this->driver}] driver.");
    }

    /**
     * Generate local disk config.
     *
     * @return string
     */
    protected function localDiskConfig()
    {
        $path = $this->publicRelativePath();

        return <<<EOT
\n
        '{$this->disk}' => [
            'driver' => 'local',
            'root' => public_path('{$path}'),
            'url' => '/{$path}',
            'visibility' => 'public',
        ],
\n
EOT;
    }

    /**
     * Generate S3 disk config.
     *
     * @return string
     */
    protected function s3DiskConfig()
    {
        $envPrefix = strtoupper($this->disk);

        return <<<EOT
\n
        '{$this->disk}' => [
            'driver' => 's3',
            'key' => env('{$envPrefix}_AWS_ACCESS_KEY_ID'),
            'secret' => env('{$envPrefix}_AWS_SECRET_ACCESS_KEY'),
            'region' => env('{$envPrefix}_AWS_DEFAULT_REGION'),
            'bucket' => env('{$envPrefix}_AWS_BUCKET'),
            'url' => env('{$envPrefix}_AWS_URL'),
        ],
\n
EOT;
    }

    /**
     * Generate public relative path from disk key.
     *
     * @return string
     */
    protected function publicRelativePath()
    {
        return str_replace('assets_', 'assets/', $this->disk);
    }

    /**
     * Check if filesystem disk exists.
     *
     * @param string $disk
     * @return bool
     */
    protected function diskExists($disk)
    {
        return Arr::has(include config_path('filesystems.php'), "disks.{$disk}");
    }

    /**
     * Migrate container folder.
     *
     * @return $this
     */
    protected function migrateFolder()
    {
        if (! $this->localPath) {
            return $this;
        }

        if (! $this->files->exists($this->localPath)) {
            throw new NotFoundException("Assets folder cannot be found at [path].", $this->localPath);
        }

        $this->files->copyDirectory($this->localPath, public_path($this->publicRelativePath()));

        return $this;
    }

    /**
     * Migrate container meta.
     *
     * @return $this
     */
    protected function migrateMeta()
    {
        $this->refreshFilesystems();

        try {
            $this->migrateExplicitMetaData();
            $this->migrateBlankMeta();
        } catch (\Exception $exception) {
            $this->addWarning(
                'Could not generate asset meta.',
                "Please ensure proper configuration on [{$this->disk}] disk in [config/filesystems.php].\n" .
                "Once properly configured, run `php please assets:meta` to complete meta migration."
            );
        }

        return $this;
    }

    /**
     * Refresh filesystems config, since we manually injected new config directly into the PHP file.
     */
    protected function refreshFilesystems()
    {
        $updatedFilesystemsConfig = include config_path('filesystems.php');

        config(['filesystems' => $updatedFilesystemsConfig]);
    }

    /**
     * Migrate explicit meta data (ie. `alt` and `focus`).
     */
    protected function migrateExplicitMetaData()
    {
        $storage = Storage::disk($this->disk);

        $this->metaData->each(function ($data, $file) use ($storage) {
            $storage->put(preg_replace('/\/*([^\/]+)$/', '/.meta/$1.yaml', $file), YAML::dump($data));
        });
    }

    /**
     * Migrate blank meta on all assets to prevent control panel errors.
     */
    protected function migrateBlankMeta()
    {
        Artisan::call('statamic:assets:meta', ['container' => $this->handle]);
    }
}
