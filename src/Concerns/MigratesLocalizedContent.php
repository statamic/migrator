<?php

namespace Statamic\Migrator\Concerns;

trait MigratesLocalizedContent
{
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
        // TODO: Figure out how to fix php 7.2 trait collision using trait aliasing?
        $container = new class {
            use GetsSettings;

            public function getLocales()
            {
                return collect($this->getSetting('system.locales', []));
            }
        };

        return $container->getLocales();
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
     * Get zipped locale and migrated site keys.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getZippedLocaleAndSiteKeys()
    {
        return $this->getLocaleKeys()
            ->zip($this->getMigratedSiteKeys())
            ->map(function ($zipped) {
                return [
                    'locale' => $zipped[0],
                    'site' => $zipped[1],
                ];
            });
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
