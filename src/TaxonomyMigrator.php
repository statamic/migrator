<?php

namespace Statamic\Migrator;

use Statamic\Support\Arr;

class TaxonomyMigrator extends Migrator
{
    use Concerns\GetsSettings,
        Concerns\MigratesContent,
        Concerns\MigratesLocalizedContent,
        Concerns\MigratesFile,
        Concerns\MigratesRoute,
        Concerns\MigratesFieldsetsToBlueprints,
        Concerns\ThrowsFinalWarnings;

    protected $config;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(base_path($relativePath = "content/taxonomies/{$this->handle}"))
            ->validateUnique()
            ->parseYamlConfig()
            ->copyDirectoryFromSiteToNewPath($relativePath)
            ->migrateTerms()
            ->migrateYamlConfig()
            ->migrateFieldsetsToBlueprints("taxonomies/{$this->handle}")
            ->throwFinalWarnings();
    }

    /**
     * Specify unique paths that shouldn't be overwritten.
     *
     * @return array
     */
    protected function uniquePaths()
    {
        return [
            $this->newPath(),
            $this->newPath("../{$this->handle}.yaml"),
        ];
    }

    /**
     * Parse yaml config.
     *
     * @return $this
     */
    protected function parseYamlConfig()
    {
        $this->config = $this->getSourceYaml("content/taxonomies/{$this->handle}.yaml", true);

        return $this;
    }

    /**
     * Migrate terms.
     *
     * @return $this
     */
    protected function migrateTerms()
    {
        $files = $this->files->exists($this->newPath())
            ? $this->files->files($this->newPath())
            : [];

        collect($files)
            ->mapWithKeys(function ($term) {
                return [$term->getPathname() => $this->getSourceYaml($term->getPathname(), true)];
            })
            ->each(function ($term, $path) {
                $this->saveMigratedYaml(
                    $this->migrateTerm($term, pathinfo($path)['filename']),
                    $path
                );
            });

        return $this;
    }

    /**
     * Migrate term.
     *
     * @param \Illuminate\Support\Collection $term
     * @param string $slug
     * @return array
     */
    protected function migrateTerm($term, $slug)
    {
        $fieldset = $this->getTermFieldset($term);

        $localizations = $this
            ->pullLocalizedTermContent($term)
            ->map(function ($term, $locale) use ($slug) {
                return $this->mergeLocalizedTermSlug($term, $locale, $slug);
            })
            ->map(function ($term) use ($fieldset) {
                return $this->migrateContent($term, $fieldset, false);
            });

        $term = $this->migrateContent($term, $fieldset);

        if (! $term->has('title')) {
            $term->put('title', $slug);
        }

        if ($localizations->isNotEmpty()) {
            $term->put('localizations', $localizations);
        }

        return $term;
    }

    /**
     * Get term fieldset.
     *
     * @param \Illuminate\Support\Collection $term
     * @return string
     */
    protected function getTermFieldset($term)
    {
        $fieldset = $term['fieldset']
            ?? $this->config['fieldset']
            ?? $this->getSetting('theming.default_term_fieldset')
            ?? $this->getSetting('theming.default_fieldset');

        $this->addMigratableFieldset($fieldset);

        return $fieldset;
    }

    /**
     * Pull localized term content.
     *
     * @param \Illuminate\Support\Collection $term
     * @return \Illuminate\Support\Collection
     */
    protected function pullLocalizedTermContent($term)
    {
        return $this
            ->getZippedLocaleAndSiteKeys()
            ->filter(function ($zipped) use ($term) {
                return $term->has($zipped['locale']);
            })
            ->mapWithKeys(function ($zipped) use ($term) {
                return [$zipped['site'] => $term->pull($zipped['locale'])];
            });
    }

    /**
     * Merge localized term slug into term's data.
     *
     * @param array $term
     * @param string $locale
     * @param string $slug
     * @return array
     */
    protected function mergeLocalizedTermSlug($term, $locale, $slug)
    {
        $localizedSlug = Arr::get($this->config, "slugs.{$locale}.{$slug}");

        return $localizedSlug
            ? array_merge($term, ['slug' => $localizedSlug])
            : $term;
    }

    /**
     * Migrate yaml config.
     * * @return $this
     */
    protected function migrateYamlConfig()
    {
        if ($this->isMultisite()) {
            $this->config->put('sites', $this->getMigratedSiteKeys()->all());
        }

        if ($route = $this->migrateRoute("taxonomies.{$this->handle}")) {
            $this->config->put('route', $route);
        }

        $this->config->forget('slugs');
        $this->config->forget('fieldset');

        $this->saveMigratedYaml($this->config, $this->newPath("../{$this->handle}.yaml"));

        return $this;
    }
}
