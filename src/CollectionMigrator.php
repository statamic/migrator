<?php

namespace Statamic\Migrator;

use Statamic\Migrator\YAML;

class CollectionMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\MigratesRoute;

    protected $availableTaxonomies;
    protected $usedTaxonomies;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(base_path($relativePath = "content/collections/{$this->handle}"))
            ->validateUnique()
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
     * Parse available taxonomies.
     *
     * @return $this
     */
    public function parseAvailableTaxonomies()
    {
        $this->availableTaxnomies = collect($this->files->files($this->sitePath('content/taxonomies')))
            ->map
            ->getFilenameWithoutExtension();

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
        $this->files->makeDirectory($this->newPath());

        collect($this->files->files($this->sitePath($relativePath)))
            ->reject(function ($file) {
                return $file->getFilename() === 'folder.yaml';
            })
            ->mapWithKeys(function ($file) {
                return [$file->getFilename() => $this->getSourceYaml($file)];
            })
            ->each(function ($entry, $filename) {
                $this->saveMigratedWithYamlFrontMatter($this->migrateEntry($entry), $this->migratePath($filename));
            });

        return $this;
    }

    /**
     * Migrate entry.
     *
     * @param string $entry
     * @return string
     */
    protected function migrateEntry($entry)
    {
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
        $usedTaxonomies = $this->availableTaxnomies
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
     * @return $string
     */
    protected function migratePath($filename)
    {
        return $this->newPath(preg_replace('/(.*)\.[^\.]+/', '$1.md', $filename));
    }

    /**
     * Migrate yaml config.
     *
     * @return $this
     */
    protected function migrateYamlConfig()
    {
        $config = $this->getSourceYaml("content/collections/{$this->handle}/folder.yaml", true);

        if ($fieldset = $config->get('fieldset')) {
            $config->put('blueprints', [$fieldset]);
            $config->forget('fieldset');
        }

        if ($route = $this->migrateRoute("collections.{$this->handle}")) {
            $config->put('route', $route);
        }

        if ($taxonomies = $this->usedTaxonomies) {
            $config->put('taxonomies', $taxonomies->all());
        }

        $this->saveMigratedYaml($config, $this->newPath("../{$this->handle}.yaml"));

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
