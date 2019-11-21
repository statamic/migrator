<?php

namespace Statamic\Migrator;

use Statamic\Migrator\Exceptions\MigratorSkippedException;

class SettingsMigrator extends Migrator
{
    use Concerns\MigratesFile;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        if ($this->handle) {
            return $this->migrateSingle();
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
            ;
    }

    /**
     * Perform migration on routes.
     *
     * @return $this
     */
    protected function migrateCp()
    {
        $this->validateFreshStatamicConfig('cp.php');

        $cp = $this->getSourceYaml('settings/cp.yaml');

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
        $this->validateFreshStatamicConfig('routes.php');

        $routes = $this->getSourceYaml('settings/routes.yaml');

        Configurator::file('statamic/routes.php')->merge('routes', $routes['routes'] ?? []);
        Configurator::file('statamic/routes.php')->merge('vanity', $routes['vanity'] ?? []);
        Configurator::file('statamic/routes.php')->merge('redirect', $routes['redirect'] ?? []);

        return $this;
    }

    /**
     * Perform migration on system.
     *
     * @return $this
     */
    protected function migrateSystem()
    {
        $this->validateFreshStatamicConfig('system.php');
        $this->validateFreshStatamicConfig('sites.php');

        $system = $this->getSourceYaml('settings/system.yaml');

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
        $sites = collect($system['locales'])
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
}
