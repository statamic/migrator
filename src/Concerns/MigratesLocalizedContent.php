<?php

namespace Statamic\Migrator\Concerns;

trait MigratesLocalizedContent
{
    use GetsSettings;

    /**
     * Check if site has multiple locales.
     *
     * @return bool
     */
    protected function isMultisite()
    {
        return $this->getLocales()->count() > 1;
    }

    /**
     * Get locales.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getLocales()
    {
        return collect($this->getSetting('system.locales', []));
    }

    /**
     * Get locale keys.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getLocaleKeys()
    {
        return $this->getLocales()->keys();
    }

    /**
     * Get migrated site keys.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getMigratedSiteKeys()
    {
        $sites = $this->getLocaleKeys();

        return $sites->put(0, 'default');
    }

    /**
     * Migrate site.
     *
     * @param string $relativePath
     * @return string
     */
    protected function migrateSite($relativePath)
    {
        $parts = collect(explode('/', $relativePath));

        $locale = $parts->shift();

        if (! $this->getLocaleKeys()->contains($locale)) {
            return 'default';
        }

        return $locale;
    }

    /**
     * Migrate localized filename.
     *
     * @param string $relativePath
     * @return string
     */
    protected function migrateLocalizedFilename($relativePath)
    {
        $parts = collect(explode('/', $relativePath));

        $locale = $parts->shift();

        if (! $this->getLocaleKeys()->contains($locale)) {
            return $relativePath;
        }

        return $parts->implode('/');
    }
}
