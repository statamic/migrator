<?php

namespace Statamic\Migrator;

use Statamic\Support\Str;
use Statamic\Migrator\YAML;

class CollectionMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\MigratesRoute;

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

        if (isset($entry['fieldset'])) {
            $entry['blueprint'] = $entry['fieldset'];
        }

        unset($entry['fieldset']);

        $this->migrateUsedTaxonomies($entry);

        return $entry;
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
        if ($fieldset = $this->config->get('fieldset')) {
            $this->config->put('blueprints', [$fieldset]);
            $this->config->forget('fieldset');
        }

        if ($route = $this->migrateRoute("collections.{$this->handle}")) {
            $this->config->put('route', $route);
        }

        if ($taxonomies = $this->usedTaxonomies) {
            $this->config->put('taxonomies', $taxonomies->all());
        }

        switch ($this->config->get('order')) {
            case 'date':
                $this->config->put('date', true);
                $this->config->put('date_behavior', ['past' => 'public', 'future' => 'unlisted']);
                $this->config->put('sort_dir', 'desc');
                break;
            case 'number':
                $this->config->put('orderable', true);
                break;
        }

        $this->config->forget('order');

        if ($this->entryOrder) {
            $this->config->put('entry_order', collect($this->entryOrder)->sortKeys()->values()->all());
        }

        $this->saveMigratedYaml($this->config, $this->newPath("../{$this->handle}.yaml"));

        return $this;
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
