<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Migrator\YAML;
use Statamic\Support\Arr;

trait MigratesRoute
{
    /**
     * Get route using dot notation.
     *
     * @param  string  $key
     * @param  string|null  $default
     * @return $this
     */
    protected function migrateRoute($key, $default = null)
    {
        if ($this->files->exists($path = $this->sitePath('settings/routes.yaml'))) {
            $routes = YAML::parse($this->files->get($path));
        }

        return Arr::get($routes ?? [], $key, $default);
    }
}
