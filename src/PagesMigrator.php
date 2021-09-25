<?php

namespace Statamic\Migrator;

use Facades\Statamic\Migrator\UUID;
use Statamic\Facades\Path;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class PagesMigrator extends Migrator
{
    use Concerns\GetsSettings,
        Concerns\MigratesContent,
        Concerns\MigratesLocalizedContent,
        Concerns\MigratesFile,
        Concerns\MigratesFieldsetsToBlueprints,
        Concerns\ThrowsFinalWarnings;

    protected $sites = [];
    protected $entries = [];
    protected $localizedEntries = [];
    protected $structure = [];
    protected $localizedUuidMappings = [];

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(base_path('content/collections/pages'))
            ->validateUnique()
            ->parseTree()
            ->createYamlConfig()
            ->createYamlTrees()
            ->migratePagesToEntries()
            ->migrateFieldsetsToBlueprints('collections/pages')
            ->throwFinalWarnings();
    }

    /**
     * Get descriptor for use in command output.
     *
     * @return string
     */
    public static function descriptor()
    {
        return 'Pages collection/structure';
    }

    /**
     * Specify unique paths that shouldn't be overwritten.
     *
     * @return array
     */
    protected function uniquePaths()
    {
        $paths = collect([
            $this->newPath(),
            $this->newPath('../pages.yaml'),
            $this->newPath('../../trees/collections/pages.yaml'),
        ]);

        if ($this->isMultisite()) {
            $this->getMigratedSiteKeys()->each(function ($site) use (&$paths) {
                $paths->push($this->newPath("../../trees/collections/{$site}/pages.yaml"));
            });
        }

        return $paths->all();
    }

    /**
     * Parse tree.
     *
     * @return this
     */
    protected function parseTree()
    {
        $this->sites = $this->getMigratedSiteKeys()->all();

        $this->parsePageFolder($this->sitePath('content/pages'));

        return $this;
    }

    /**
     * Parse page folder.
     *
     * @param string $folder
     * @param string $key
     * @return array
     */
    protected function parsePageFolder($folder, $key = 'root')
    {
        if (! $page = $this->getPageInFolder($folder)) {
            return [];
        }

        $page['published'] = $this->migratePublishedStatus($page, $folder);
        $page['slug'] = $this->migratePageSlug($page, $key, $folder);

        $this->entries[] = $page;

        $entry = $page['id'];

        $children = collect($this->files->directories($folder))
            ->sortBy(function ($folder) {
                return str_replace('/_', '/', $folder);
            })
            ->map(function ($folder) use ($key, $entry) {
                return $this->parsePageFolder($folder, "{$key}.{$entry}");
            })
            ->filter()
            ->values()
            ->all();

        $data = collect(compact('entry', 'children'))->filter()->all();

        data_set($this->structure, $key, $data);

        if ($this->isMultisite()) {
            $this->parseLocalizedPagesInFolder($folder, $page);
        }

        return $data;
    }

    /**
     * Parse localized pages in folder.
     *
     * @param string $folder
     * @param array $pageOrigin
     */
    protected function parseLocalizedPagesInFolder($folder, $pageOrigin)
    {
        $this->getLocalizedPagesInFolder($folder)->each(function ($page, $site) use ($pageOrigin) {
            $this->localizedEntries[$site][] = $this->prepareLocalizedPage($page, $pageOrigin, $site);
        });
    }

    /**
     * Prepare localized page.
     *
     * @param array $page
     * @param array $pageOrigin
     * @param string $site
     * @return array
     */
    protected function prepareLocalizedPage($page, $pageOrigin, $site)
    {
        $localized = [
            'origin' => $pageOrigin['id'],
            'id' => $this->generateUuid($pageOrigin, $site),
            'slug' => $page['slug'] ?? $pageOrigin['slug'],
            'fieldset' => $pageOrigin['fieldset'] ?? null,
            'published' => isset($page['published']) ? $page['published'] : $pageOrigin['published'],
        ];

        $overriddenData = collect($localized)->reject(function ($value) {
            return $value === null;
        })->all();

        return array_merge($page, $overriddenData);
    }

    /**
     * Get page in folder.
     *
     * @param string $folder
     * @return string|bool
     */
    protected function getPageInFolder($folder)
    {
        $page = collect($this->files->files($folder))
            ->filter(function ($file) {
                return $file->getFilenameWithoutExtension() === 'index';
            })
            ->first();

        if (! $page) {
            return false;
        }

        return YAML::parse($page->getContents());
    }

    /**
     * Get localized pages in folder.
     *
     * @param string $folder
     * @return \Illuminate\Support\Collection
     */
    protected function getLocalizedPagesInFolder($folder)
    {
        $explicitlyLocalizedPages = collect($this->files->files($folder))
            ->keyBy
            ->getFilenameWithoutExtension()
            ->filter(function ($file, $filename) {
                return Str::endsWith($filename, '.index');
            })
            ->keyBy(function ($file, $filename) {
                return explode('.', $filename)[0];
            })
            ->filter(function ($file, $site) {
                return in_array($site, $this->sites);
            })
            ->map(function ($page) {
                return YAML::parse($page->getContents());
            });

        $implicitlyLocalizedPages = collect($this->sites)
            ->flip()
            ->forget('default')
            ->map(function ($page) {
                return [];
            });

        return $implicitlyLocalizedPages->merge($explicitlyLocalizedPages);
    }

    /**
     * Migrate page published status.
     *
     * @param string $folder
     * @return bool
     */
    protected function migratePublishedStatus($page, $folder)
    {
        if ($published = Arr::get($page, 'published')) {
            return (bool) $published;
        }

        return ! (bool) preg_match('/.*\/_[^_]*$/', Path::resolve($folder));
    }

    /**
     * Migrate page slug.
     *
     * @param array $page
     * @param string $key
     * @param string $folder
     */
    protected function migratePageSlug($page, $key, $folder)
    {
        if ($key === 'root') {
            return Str::slug($page['title']);
        }

        return preg_replace('/.*\/_*[0-9]*\.*([^\/]+)$/', '$1', Path::resolve($folder));
    }

    /**
     * Create yaml config.
     *
     * @return $this
     */
    protected function createYamlConfig()
    {
        $config = [
            'title' => 'Pages',
            'route' => '{{ parent_uri }}/{{ slug }}',
            'structure' => [
                'root' => true,
            ],
        ];

        if ($this->isMultisite()) {
            $config['sites'] = collect($this->localizedEntries)
                ->keys()
                ->prepend('default')
                ->all();
        }

        $this->saveMigratedYaml($config, $this->newPath('../pages.yaml'));

        return $this;
    }

    /**
     * Create yaml tree(s).
     *
     * @return $this
     */
    protected function createYamlTrees()
    {
        if (! $this->isMultisite()) {
            return $this->saveMigratedYaml($this->migrateTree(), $this->newPath('../../trees/collections/pages.yaml'));
        }

        $this->migrateLocalizedTrees()->each(function ($tree, $site) {
            $this->saveMigratedYaml($tree, $this->newPath("../../trees/collections/{$site}/pages.yaml"));
        });

        return $this;
    }

    /**
     * Migrate tree.
     *
     * @return array
     */
    protected function migrateTree()
    {
        if ($home = $this->structure['root']['entry'] ?? false) {
            $tree = collect($this->structure['root']['children'] ?? [])->prepend(['entry' => $home])->all();
        }

        return [
            'tree' => $tree ?? [],
        ];
    }

    /**
     * Migrate localized trees for multisite pages collection.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function migrateLocalizedTrees()
    {
        $tree['default'] = $this->migrateTree();

        $localizedTrees = collect($this->localizedUuidMappings)
            ->map(function ($mappings, $site) use ($tree) {
                return $this->migrateLocalizedTree($tree['default']['tree'], $site);
            })
            ->all();

        return collect(array_merge($tree, $localizedTrees));
    }

    /**
     * Migrate localized tree off of default tree.
     *
     * @param array $defaultTree
     * @param string $site
     * @return array
     */
    protected function migrateLocalizedTree($defaultTree, $site)
    {
        $dotted = collect(Arr::dot($defaultTree))
            ->filter(function ($uuid) {
                return is_string($uuid);
            })
            ->map(function ($uuid) use ($site) {
                return $this->localizedUuidMappings[$site][$uuid] ?? false;
            })
            ->filter()
            ->all();

        return $this->normalizeTree(Arr::undot($dotted));
    }

    /**
     * Migrate pages to entries.
     *
     * @return $this
     */
    protected function migratePagesToEntries()
    {
        $this->files->cleanDirectory($this->newPath());

        collect($this->entries)->each(function ($entry) {
            $this->saveMigratedWithYamlFrontMatter(
                $this->migrateContent($entry, $this->getPageFieldset($entry)),
                $this->generateEntryPath($entry, $this->isMultisite() ? 'default' : null)
            );
        });

        if ($this->isMultisite()) {
            collect($this->localizedEntries)->each(function ($siteEntries, $site) {
                collect($siteEntries)->each(function ($entry) use ($site) {
                    $this->saveMigratedWithYamlFrontMatter(
                        $this->migrateContent($entry, $this->getPageFieldset($entry)),
                        $this->generateEntryPath($entry, $site)
                    );
                });
            });
        }

        return $this;
    }

    /**
     * Get page fieldset.
     *
     * @param array $page
     * @return string
     */
    protected function getPageFieldset($page)
    {
        $origin = $this->getOriginEntry($page);

        $fieldset = $page['fieldset']
            ?? $origin['fieldset']
            ?? $this->getSetting('theming.default_page_fieldset')
            ?? $this->getSetting('theming.default_fieldset');

        $this->addMigratableFieldset($fieldset);

        return $fieldset;
    }

    /**
     * Generate entry path.
     *
     * @param array $entry
     * @param string|null $site
     * @param int $number
     * @return string
     */
    protected function generateEntryPath($entry, $site = null, $number = 1)
    {
        $appended = $number > 1
            ? "-{$number}"
            : null;

        $subFolder = $site
            ? "{$site}/"
            : null;

        $path = $this->newPath("{$subFolder}{$entry['slug']}{$appended}.md");

        if ($this->files->exists($path)) {
            return $this->generateEntryPath($entry, $site, ++$number);
        }

        return $path;
    }

    /**
     * Get migratable fieldsets.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getMigratableFieldsets()
    {
        return collect($this->migratableFieldsets)
            ->merge($this->allFieldsets())
            ->filter()
            ->unique()
            ->reject(function ($handle) {
                return $this->isNonExistentFieldset($handle) || $this->isNonExistentDefaultFieldset($handle);
            });
    }

    /**
     * Get all selectable fieldsets.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function allFieldsets()
    {
        if ($this->files->exists($path = base_path('site/settings/fieldsets'))) {
            $fieldsets = collect($this->files->files($path))
                ->reject(function ($fieldset) {
                    return Arr::get(YAML::parse($fieldset->getContents()), 'hide', false);
                })
                ->map
                ->getFilenameWithoutExtension();
        }

        return $fieldsets ?? collect();
    }

    /**
     * Generate new UUID for localized entry.
     *
     * @param array $pageOrigin
     * @param string $site
     * @return string
     */
    protected function generateUuid($pageOrigin, $site)
    {
        $uuid = (string) UUID::generate("{$site}-{$pageOrigin['id']}");

        $this->localizedUuidMappings[$site][$pageOrigin['id']] = $uuid;

        return $uuid;
    }

    /**
     * Recursively normalize tree keys.
     *
     * @param array $tree
     * @return array
     */
    protected function normalizeTree($tree, $depth = 1)
    {
        if (! is_array($tree)) {
            return $tree;
        }

        $branch = $depth === 1 ? array_values($tree) : $tree;

        $normalized = collect($branch)
            ->map(function ($value, $key) use ($depth) {
                return $key === 'children'
                    ? $this->normalizeTree(array_values($value), ++$depth)
                    : $this->normalizeTree($value, ++$depth);
            })
            ->all();

        return $depth === 1
            ? ['tree' => $normalized]
            : $normalized;
    }

    /**
     * Get origin entry for localized entry.
     *
     * @param array $localizedEntry
     * @return null|array
     */
    protected function getOriginEntry($localizedEntry)
    {
        return collect($this->entries)->first(function ($entry) use ($localizedEntry) {
            return $entry['id'] === Arr::get($localizedEntry, 'origin');
        });
    }
}
