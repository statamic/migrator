<?php

namespace Statamic\Migrator;

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
        $routes = $this->getSourceYaml('settings/routes.yaml');

        Configurator::file('statamic/routes.php')->merge('routes', $routes['routes'] ?? []);
        Configurator::file('statamic/routes.php')->merge('vanity', $routes['vanity'] ?? []);
        Configurator::file('statamic/routes.php')->merge('redirects', $routes['redirect'] ?? []);

        return $this;
    }

    /**
     * Perform migration on system.
     *
     * @return $this
     */
    protected function migrateSystem()
    {
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
        return collect($system['locales'])
            ->map(function ($site) {
                return [
                    'name' => $site['name'],
                    'locale' => $site['full'],
                    'url' => $site['url'],
                ];
            })
            ->all();
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
}
