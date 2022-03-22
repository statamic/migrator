<?php

namespace Statamic\Migrator;

use Illuminate\Support\Facades\Storage;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Path;
use Statamic\Migrator\Exceptions\AlreadyExistsException;
use Statamic\Migrator\Exceptions\FilesystemException;
use Statamic\Migrator\Exceptions\NotFoundException;
use Statamic\Support\Arr;

class AssetContainerMigrator extends Migrator
{
    use Concerns\GetsSettings,
        Concerns\MigratesFile,
        Concerns\MigratesFieldsetsToBlueprints,
        Concerns\ThrowsFinalWarnings;

    protected $configurator;
    protected $metaOnly = false;
    protected $disk;
    protected $container;
    protected $localPath;
    protected $s3Path;
    protected $metaData;
    protected $fieldset;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->instantiateConfigurator()
            ->setNewPath(base_path($relativePath = "content/assets/{$this->handle}.yaml"))
            ->parseDiskKey()
            ->parseAssetContainer($relativePath)
            ->parseYamlConfig();

        if ($this->metaOnly) {
            return $this
                ->migrateMeta()
                ->throwFinalWarnings();
        }

        $this
            ->validateUnique()
            ->migrateYamlConfig()
            ->migrateDisk()
            ->migrateFolder()
            ->migrateMeta()
            ->migrateFieldset()
            ->migrateGitPath()
            ->throwFinalWarnings();
    }

    /**
     * Instantiate configurator.
     *
     * @return $this
     */
    protected function instantiateConfigurator()
    {
        $this->configurator = Configurator::file('filesystems.php');

        return $this;
    }

    /**
     * Determine whether to run on meta only.
     *
     * @param bool $metaOnly
     * @return $this
     */
    public function metaOnly($metaOnly)
    {
        $this->metaOnly = $metaOnly;

        return $this;
    }

    /**
     * Parse disk key.
     *
     * @return string
     */
    protected function parseDiskKey()
    {
        $exists = $this->overwrite ? false : $this->diskExists('assets');

        $this->disk = $exists || count($this->files->files($this->sitePath('content/assets'))) > 1
            ? 'assets_'.strtolower($this->handle)
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
        if ($this->overwrite) {
            return $this;
        }

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
    protected function parseYamlConfig()
    {
        $config = collect($this->container);

        $this->driver = $this->parseDriver($config);
        $this->localPath = $this->parseLocalPath($config);
        $this->s3Path = $this->parseS3Path($config);
        $this->metaData = $this->parseMeta($config);
        $this->fieldset = $this->parseFieldset($config);

        $config->put('disk', $this->disk);

        $config->forget('fieldset');

        $this->container = $config->only('title', 'disk', 'blueprint')->all();

        return $this;
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
     * @param array $config
     * @return null|string
     */
    protected function parseLocalPath($config)
    {
        if (Arr::get($config, 'driver', 'local') !== 'local') {
            return null;
        }

        $path = Arr::get($config, 'path');

        $path = collect(explode('/', Arr::get($config, 'path')))->filter()->last();

        if ($this->files->exists($pathFromSite = base_path("site/{$path}"))) {
            return $pathFromSite;
        } elseif ($this->files->exists($pathFromMigrator = base_path("migrator/{$path}"))) {
            return $pathFromMigrator;
        } elseif ($this->files->exists($path = base_path($path))) {
            return $path;
        }

        throw new NotFoundException('Assets folder cannot be found at path [path].', $path);
    }

    /**
     * Parse S3 path.
     *
     * @param array $config
     * @return string
     */
    protected function parseS3Path($config)
    {
        if (Arr::get($config, 'driver', 'local') !== 's3') {
            return null;
        }

        if ($path = Arr::get($config, 'path', null)) {
            $this->addWarning(
                'Subfolder `path` option no longer supported.',
                'Asset container disks now point to the root of your S3 bucket.'
            );
        }

        return $path;
    }

    /**
     * Parse meta.
     *
     * @param array $config
     * @return \Illuminate\Support\Collection
     */
    protected function parseMeta($config)
    {
        $fieldset = Arr::get($config, 'fieldset');

        return collect(Arr::get($config, 'assets', []))
            ->map(function ($metaData) use ($fieldset) {
                return $fieldset
                    ? $metaData
                    : Arr::only($metaData, ['alt', 'focus']);
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
     * Parse fieldset.
     *
     * @param string $config
     * @return string|null
     */
    protected function parseFieldset($config)
    {
        if ($fieldset = Arr::get($config, 'fieldset')) {
            return $fieldset;
        }

        $defaultFieldset = $this->getSetting('theming.default_asset_fieldset');

        return $this->files->exists($this->sitePath("settings/fieldsets/{$defaultFieldset}.yaml"))
            ? $defaultFieldset
            : null;
    }

    /**
     * Migrate yaml config.
     *
     * @return $this
     */
    protected function migrateYamlConfig()
    {
        return $this->saveMigratedYaml($this->container);
    }

    /**
     * Migrate disk.
     *
     * @return $this
     * @throws \Exception
     */
    protected function migrateDisk()
    {
        $diskConfig = $this->diskConfig();

        try {
            $this->configurator
                ->mergeSpaciously('disks', [$this->disk => $this->diskConfig()])
                ->normalize();
        } catch (\Exception $exception) {
            throw new FilesystemException('Cannot migrate filesystem disk config.');
        }

        return $this;
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

        throw new FilesystemException("Cannot migrate asset container with [{$this->driver}] driver.");
    }

    /**
     * Generate local disk config.
     *
     * @return array
     */
    protected function localDiskConfig()
    {
        $path = $this->publicRelativePath();

        return [
            'driver' => 'local',
            'root' => "public_path('{$path}')",
            'url' => "/{$path}",
            'visibility' => 'public',
            'throw' => false,
        ];
    }

    /**
     * Generate S3 disk config.
     *
     * @return array
     */
    protected function s3DiskConfig()
    {
        $envPrefix = strtoupper($this->disk);

        return [
            'driver' => 's3',
            'key' => "env('{$envPrefix}_AWS_ACCESS_KEY_ID')",
            'secret' => "env('{$envPrefix}_AWS_SECRET_ACCESS_KEY')",
            'region' => "env('{$envPrefix}_AWS_DEFAULT_REGION')",
            'bucket' => "env('{$envPrefix}_AWS_BUCKET')",
            'url' => "env('{$envPrefix}_AWS_URL')",
            'endpoint' => "env('{$envPrefix}_AWS_ENDPOINT')",
            'use_path_style_endpoint' => "env('{$envPrefix}_AWS_USE_PATH_STYLE_ENDPOINT', false)",
            'throw' => false,
        ];
    }

    /**
     * Attempt disk replacement.
     *
     * @param string $diskConfig
     * @return bool
     */
    protected function attemptDiskReplacement($diskConfig)
    {
        if (! $this->diskExists($this->disk)) {
            return false;
        }

        $this->configurator->set("disks.{$this->disk}", $diskConfig)->normalize();

        return true;
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
        $this->configurator->refresh();

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
        $this->configurator->refresh();

        $envNote = $this->driver === 's3' ? ' and [.env]' : '';

        try {
            $this->migrateExplicitMetaData();
            $this->migrateBlankMeta();
        } catch (\Exception $exception) {
            $this->addWarning(
                'Could not generate asset meta.',
                "Please ensure proper configuration on your [{$this->disk}] disk in [config/filesystems.php]{$envNote},\n".
                "Then run `php please migrate:asset-container {$this->handle} --meta-only` to complete meta migration."
            );
        }

        return $this;
    }

    /**
     * Migrate explicit meta data (ie. `alt` and `focus`).
     */
    protected function migrateExplicitMetaData()
    {
        $storage = Storage::disk($this->disk);

        $this->metaData->each(function ($data, $file) use ($storage) {
            $storage->put($this->metaPath($file), YAML::dump($data));
        });
    }

    /**
     * Get proper meta path for meta migration.
     *
     * @param string $file
     * @return string
     */
    protected function metaPath($file)
    {
        $path = preg_replace('/(\/*)([^\/]+)$/', '$1.meta/$2.yaml', Path::resolve($file));
        $subFolder = $this->s3Path ? "{$this->s3Path}/" : '';

        return $subFolder.$path;
    }

    /**
     * Migrate blank meta on all assets to prevent control panel errors.
     */
    protected function migrateBlankMeta()
    {
        $container = AssetContainer::findByHandle($this->handle);

        if (! $container) {
            throw new \Exception;
        }

        $container->assets()->each(function ($asset) {
            $asset->hydrate()->save();
        });
    }

    /**
     * Migrate fieldset.
     *
     * @return $this
     */
    protected function migrateFieldset()
    {
        if ($this->fieldset) {
            $this->migrateFieldsetToBlueprint('assets', $this->fieldset, $this->handle);
        }

        return $this;
    }

    /**
     * Migrate git path.
     *
     * @return $this
     */
    protected function migrateGitPath()
    {
        if ($this->driver !== 'local') {
            return $this;
        }

        $path = "public_path('".$this->publicRelativePath()."')";

        Configurator::file('statamic/git.php')->merge('paths', [$path]);

        return $this;
    }
}
