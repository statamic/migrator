<?php

namespace Statamic\Migrator;

use Statamic\Support\Str;
use Statamic\Migrator\YAML;

class CollectionMigrator extends Migrator
{
    use Concerns\MigratesContent,
        Concerns\MigratesRoute,
        Concerns\MigratesFile;

    protected $config;
    protected $defaultFieldset;
    protected $availableTaxonomies;
    protected $usedTaxonomies;
    protected $entryOrder;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(base_path($relativePath = "content/collections/{$this->handle}"))
            ->validateUnique()
            ->parseYamlConfig()
            ->parseAvailableTaxonomies()
            ->migrateEntries($relativePath)
            ->migrateYamlConfig()
            ->deleteOldConfig();
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
        $this->config = $this->getSourceYaml("content/collections/{$this->handle}/folder.yaml", true);

        return $this;
    }

    /**
     * Parse available taxonomies.
     *
     * @return $this
     */
    protected function parseAvailableTaxonomies()
    {
        $path = $this->sitePath('content/taxonomies');

        $this->availableTaxonomies = $this->files->exists($path)
            ? collect($this->files->files($path))->map->getFilenameWithoutExtension()
            : collect();

        return $this;
    }

    /**
     * Migrate entries.
     *
     * @param string $relativePath
     * @return $this
     */
    protected function migrateEntries($relativePath)
    {
        $this->files->cleanDirectory($this->newPath());

        collect($this->files->files($this->sitePath($relativePath)))
            ->reject(function ($file) {
                return $file->getFilename() === 'folder.yaml';
            })
            ->mapWithKeys(function ($file) {
                return [$file->getFilename() => $this->getSourceYaml($file->getPathname())];
            })
            ->each(function ($entry, $filename) {
                $this->saveMigratedWithYamlFrontMatter(
                    $this->migrateEntry($entry, $filename),
                    $this->migratePath($filename, $entry)
                );
            });

        return $this;
    }

    /**
     * Migrate entry.
     *
     * @param array $entry
     * @return string
     */
    protected function migrateEntry($entry, $filename)
    {
        if (Str::startsWith($filename, '_')) {
            $entry['published'] = false;
        }

        $this->migrateUsedTaxonomies($entry);

        return $this->migrateContent($entry, $this->getEntryFieldset($entry));
    }

    /**
     * Get entry fieldset.
     *
     * @param array $entry
     * @return string
     */
    protected function getEntryFieldset($entry)
    {
        return $entry['fieldset']
            ?? $this->config['fieldset']
            ?? $this->getSetting('theming.default_entry_fieldset')
            ?? $this->getSetting('theming.default_fieldset');
    }

    /**
     * Migrate used taxonomies.
     *
     * @param mixed $entry
     */
    protected function migrateUsedTaxonomies($entry)
    {
        $usedTaxonomies = $this->availableTaxonomies
            ->filter(function ($taxonomy) use ($entry) {
                return ! empty($entry[$taxonomy]) && is_array($entry[$taxonomy]);
            });

        if ($usedTaxonomies->isEmpty()) {
            return;
        }

        $this->usedTaxonomies = collect($this->usedTaxonomies)
            ->merge($usedTaxonomies)
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Migrate path.
     *
     * @param string $filename
     * @param array $entry
     * @return $string
     */
    protected function migratePath($filename, $entry)
    {
        // Ensure file has .md extension.
        $filename = preg_replace('/(.*)\.[^\.]+/', '$1.md', $filename);

        // Remove `_` draft entry prefix.
        $filename = preg_replace('/^_(.*)$/', '$1', $filename);

        // If filename has order key, store order and remove order key.
        if ($this->config->get('order') === 'number') {
            preg_match($regex = '/^([0-9]+)\./', $filename, $matches);
            $this->entryOrder[$matches[1]] = $entry['id'];
            $filename = preg_replace($regex, '', $filename);
        }

        return $this->newPath($filename);
    }

    /**
     * Migrate yaml config.
     *
     * @return $this
     */
    protected function migrateYamlConfig()
    {
        $reservedKeys = collect(['title', 'template']);

        if ($fieldset = $this->config->get('fieldset')) {
            $this->config->forget('fieldset');
            $this->config->put('blueprints', [$fieldset]);
            $reservedKeys->push('blueprints');
        }

        if ($route = $this->migrateRoute("collections.{$this->handle}")) {
            $this->config->put('route', $route);
            $reservedKeys->push('route');
        }

        if ($taxonomies = $this->usedTaxonomies) {
            $this->config->put('taxonomies', $taxonomies->all());
            $reservedKeys->push('taxonomies');
        }

        if ($this->config->get('order') === 'date') {
            $this->config->put('date', true);
            $this->config->put('date_behavior', ['past' => 'public', 'future' => 'unlisted']);
            $this->config->put('sort_dir', 'desc');
            $reservedKeys->push('date');
            $reservedKeys->push('date_behavior');
            $reservedKeys->push('sort_dir');
        }

        $this->config->forget('order');

        if ($this->entryOrder) {
            $this->config->put('structure', $this->migrateEntryOrderToStructure());
            $reservedKeys->push('structure');
        }

        if ($injectable = $this->config->except($reservedKeys)->all()) {
            $this->config->put('inject', $injectable);
            $this->config->forget(array_keys($injectable));
        }

        $this->saveMigratedYaml($this->config, $this->newPath("../{$this->handle}.yaml"));

        return $this;
    }

    /**
     * Migrate entry order to structure.
     *
     * @return array
     */
    protected function migrateEntryOrderToStructure()
    {
        $tree = collect($this->entryOrder)
            ->sortKeys()
            ->values()
            ->map(function ($id) {
                return ['entry' => $id];
            })
            ->all();

        return [
            'max_depth' => 1,
            'tree' => $tree,
        ];
    }

    /**
     * Delete old folder.yaml config from copied folder.
     *
     * @return $this
     */
    protected function deleteOldConfig()
    {
        $this->files->delete($this->newPath('folder.yaml'));

        return $this;
    }
}
