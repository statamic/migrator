<?php

namespace Statamic\Migrator\Concerns;

use Illuminate\Support\Facades\File;
use Statamic\Migrator\YAML;
use Statamic\Support\Arr;

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

    /**
     * Checks if fieldset is a non-existent default fieldset from settings.
     *
     * This is important because if a default fieldset is referenced but does not exist, the content
     * referencing it should be using the default fieldset in core (which is just title, slug, and content).
     *
     * @return bool
     */
    protected function isNonExistentDefaultFieldset($handle, $defaultFieldsetSetting)
    {
        $defaultFieldsets = collect([
            $this->getSetting($defaultFieldsetSetting),
            $this->getSetting('theming.default_fieldset'),
        ]);

        if (! $defaultFieldsets->contains($handle)) {
            return false;
        }

        return $defaultFieldsets
            ->filter(function ($handle) {
                return $this->files->exists($this->sitePath("settings/fieldsets/{$handle}.yaml"));
            })
            ->isEmpty();
    }
}
