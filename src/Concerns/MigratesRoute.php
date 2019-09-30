<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Support\Arr;
use Statamic\Migrator\YAML;

trait MigratesRoute
{
    /**
     * Get route using dot notation.
     *
     * @param string $key
     * @param string|null $default
     * @return $this
     */
    protected function migrateRoute($key, $default = null)
    {
        if ($this->files->exists($path = base_path('site/settings/routes.yaml'))) {
            $routes = YAML::parse($this->files->get($path));
        }

        return Arr::get($routes ?? [], $key, $default);
    }
}
