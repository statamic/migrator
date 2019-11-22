<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Support\Arr;
use Statamic\Migrator\YAML;
use Illuminate\Support\Facades\File;

trait GetsSettings
{
    /**
     * Get setting.
     *
     * @param string $dottedPath
     * @param mixed $default
     * @return mixed
     */
    protected function getSetting($dottedPath, $default = null)
    {
        $pathParts = collect(explode('.', $dottedPath));
        $file = $pathParts->shift();
        $dottedPath = $pathParts->implode('.');

        $path = base_path("site/settings/{$file}.yaml");

        $settings = File::exists($path)
            ? YAML::parse(File::get($path))
            : [];

        return Arr::get($settings, $dottedPath, $default);
    }
}
