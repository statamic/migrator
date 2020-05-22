<?php

namespace Statamic\Migrator;

use Statamic\Migrator\Exceptions\MigratorSkippedException;
use Statamic\Support\Arr;

class SettingsMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\MigratesRoles,
        Concerns\ThrowsFinalWarnings;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        if ($this->handle) {
            return $this->migrateSingle()->throwFinalWarnings();
        }

        $this
            // ->migrateAssets()
            // ->migrateCaching()
            ->migrateCp()
            // ->migrateDebug()
            // ->migrateEmail()
            ->migrateRoutes()
            // ->migrateSearch()
            ->migrateSystem()
            // ->migrateTheming()
            ->migrateUsers()
            ->throwFinalWarnings();
    }

    /**
     * Migrate cp settings.
     *
     * @return $this
     */
    protected function migrateCp()
    {
        $this->validate('cp.php');

        $cp = $this->parseSettingsFile('cp.yaml');

        Configurator::file($configFile = 'statamic/cp.php')
            ->set('start_page', $cp['start_page'] ?? false)
            ->set('date_format', $cp['date_format'] ?? false)
            ->merge('widgets', $cp['widgets'] ?? [])
            ->set('pagination_size', $cp['pagination_size'] ?? false)
            ->ifNoChanges($this->throwNoChangesException($configFile));

        return $this;
    }

    /**
     * Migrate routes.
     *
     * @return $this
     */
    protected function migrateRoutes()
    {
        $routes = $this->parseSettingsFile('routes.yaml');

        if (Router::file('web.php')->has($routes) && ! $this->overwrite) {
            throw new MigratorSkippedException('Routes file [routes/web.php] has already been modified.');
        }

        Router::file('web.php')
            ->appendRoutes($routes['routes'] ?? [])
            ->appendRedirects($routes['vanity'] ?? [])
            ->appendPermanentRedirects($routes['redirect'] ?? []);

        return $this;
    }

    /**
     * Migrate system settings.
     *
     * @return $this
     */
    protected function migrateSystem()
    {
        $this->validate(['system.php', 'sites.php']);

        $system = $this->parseSettingsFile('system.yaml');

        Configurator::file($configFile = 'statamic/sites.php')
            ->mergeSpaciously('sites', $this->migrateLocales($system))
            ->ifNoChanges($this->throwNoChangesException($configFile));

        return $this;
    }

    /**
     * Migrate locales to sites.
     *
     * @param array $system
     * @return array
     */
    protected function migrateLocales($system)
    {
        $locales = $system['locales'] ?? [];
        $defaultLocale = collect($locales)->keys()->first();

        return collect($locales)
            ->mapWithKeys(function ($site, $handle) use ($defaultLocale) {
                return [
                    $handle === $defaultLocale ? 'default' : $handle => [
                        'name' => $site['name'] ?? "config('app.name')",
                        'locale' => $site['full'] ?? 'en_US',
                        'url' => $site['url'],
                    ],
                ];
            })
            ->all();
    }

    /**
     * Migrate user settings.
     *
     * @return $this
     */
    protected function migrateUsers()
    {
        $this->validate('users.php');

        $users = $this->parseSettingsFile('users.yaml');

        Configurator::file($configFile = 'statamic/users.php')
            ->set('avatars', Arr::get($users, 'enable_gravatar', false) ? 'gravatar' : 'initials')
            ->set('new_user_roles', $this->migrateRoles(Arr::get($users, 'new_user_roles', [])) ?: false)
            ->ifNoChanges($this->throwNoChangesException($configFile));

        return $this;
    }

    /**
     * Perform migration on single settings file.
     *
     * @return $this
     */
    protected function migrateSingle()
    {
        $migrateMethod = 'migrate'.ucfirst($this->handle);

        return $this->{$migrateMethod}();
    }

    /**
     * Validate statamic configs.
     *
     * @param string|array $configFiles
     */
    protected function validate($configFiles)
    {
        collect($configFiles)->each(function ($config) {
            $this->validateFreshStatamicConfig($config);
        });
    }

    /**
     * Validate fresh statamic config.
     *
     * @throws AlreadyExistsException
     * @return $this
     */
    protected function validateFreshStatamicConfig($configFile)
    {
        if ($this->overwrite) {
            return $this;
        }

        $currentConfig = $this->files->get(config_path("statamic/{$configFile}"));
        $defaultConfig = $this->files->get("vendor/statamic/cms/config/{$configFile}");
        $stubbedConfig = null;

        if ($this->files->exists($stubPath = "vendor/statamic/cms/src/Console/Commands/stubs/config/{$configFile}.stub")) {
            $stubbedConfig = $this->files->get($stubPath);
        }

        if ($currentConfig === $defaultConfig || $currentConfig === $stubbedConfig) {
            return $this;
        }

        throw new MigratorSkippedException("Config file [config/statamic/{$configFile}] has already been modified.");
    }

    /**
     * Parse settings file.
     *
     * @param string $file
     * @return $this
     */
    protected function parseSettingsFile($file)
    {
        $path = $this->sitePath("settings/{$file}");

        if (preg_match("/[\"']\{env:(.*)\}[\"']/", $this->files->get($path))) {
            $this->addWarning("There were {env:} references in [site/settings/{$file}] that may need to be configured in your new .env file.");
        }

        return $this->getSourceYaml($path);
    }

    /**
     * Throw no changes exception.
     *
     * @param string $configFile
     * @throws MigratorSkippedException
     */
    protected function throwNoChangesException($configFile)
    {
        return function () use ($configFile) {
            if (! $this->overwrite) {
                throw new MigratorSkippedException("Config file [config/{$configFile}] did not require any changes.");
            }
        };
    }
}
