<?php

namespace Statamic\Migrator;

use Statamic\Migrator\Exceptions\MigratorSkippedException;

class SettingsMigrator extends Migrator
{
    use Concerns\MigratesFile,
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
            // ->migrateUsers()
            ->throwFinalWarnings();
    }

    /**
     * Perform migration on routes.
     *
     * @return $this
     */
    protected function migrateCp()
    {
        $this->validate('cp.php');

        $cp = $this->parseSettingsFile('cp.yaml');

        Configurator::file('statamic/cp.php')->set('start_page', $cp['start_page'] ?? false);
        Configurator::file('statamic/cp.php')->set('date_format', $cp['date_format'] ?? false);
        Configurator::file('statamic/cp.php')->merge('widgets', $cp['widgets'] ?? []);
        Configurator::file('statamic/cp.php')->set('pagination_size', $cp['pagination_size'] ?? false);

        return $this;
    }

    /**
     * Perform migration on routes.
     *
     * @return $this
     */
    protected function migrateRoutes()
    {
        $routes = $this->parseSettingsFile('routes.yaml');

        if (Router::file('web.php')->has($routes) && ! $this->overwrite) {
            throw new MigratorSkippedException("Routes file [routes/web.php] has already been modified.");
        }

        Router::file('web.php')->appendRoutes($routes['routes'] ?? []);
        Router::file('web.php')->appendRedirects($routes['vanity'] ?? []);
        Router::file('web.php')->appendPermanentRedirects($routes['redirect'] ?? []);

        return $this;
    }

    /**
     * Perform migration on system.
     *
     * @return $this
     */
    protected function migrateSystem()
    {
        $this->validate(['system.php', 'sites.php']);

        $system = $this->parseSettingsFile('system.yaml');

        Configurator::file('statamic/sites.php')->mergeSpaciously('sites', $this->migrateLocales($system));

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
        $sites = collect($system['locales'] ?? [])
            ->map(function ($site) {
                return [
                    'name' => $site['name'] ?? "config('app.name')",
                    'locale' => $site['full'] ?? 'en_US',
                    'url' => $site['url'],
                ];
            });

        if ($sites->count() === 1) {
            return ['default' => $sites->first()];
        }

        return $sites->all();
    }

    /**
     * Perform migration on single settings file.
     *
     * @return $this
     */
    protected function migrateSingle()
    {
        $migrateMethod = 'migrate' . ucfirst($this->handle);

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

        if ($currentConfig !== $defaultConfig) {
            throw new MigratorSkippedException("Config file [config/statamic/{$configFile}] has already been modified.");
        }
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
}
