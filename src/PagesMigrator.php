<?php

namespace Statamic\Migrator;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Facades\Path;
use Statamic\Migrator\YAML;
use Statamic\Migrator\Exceptions\AlreadyExistsException;

class PagesMigrator extends Migrator
{
    use Concerns\MigratesFile;

    protected $entries = [];
    protected $structure = [];
    protected $usedBlueprints = [];

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(base_path("content/collections/pages"))
            ->validateUnique()
            ->parseTree()
            ->createYamlConfig()
            ->migratePagesToEntries();
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
        return [
            $this->newPath(),
            $this->newPath('../pages.yaml'),
            $this->newPath('../../structures/pages.yaml'),
        ];
    }

    /**
     * Parse tree.
     *
     * @return this
     */
    protected function parseTree()
    {
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
        if (! $this->files->exists($path = "{$folder}/index.md")) {
            return [];
        }

        $page = YAML::parse($this->files->get($path));

        $page['slug'] = $this->migratePageSlug($page, $key, $folder);

        $this->entries[] = $page;
        $this->usedBlueprints[] = $page['fieldset'] ?? null;

        $entry = $page['id'];

        $children = collect($this->files->directories("$folder"))
            ->map(function ($folder) use ($key, $entry) {
                return $this->parsePageFolder($folder, "{$key}.{$entry}");
            })
            ->all();

        $data = collect(compact('entry', 'children'))->filter()->all();

        data_set($this->structure, $key, $data);

        return $data;
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
            'blueprints' => $this->migrateConfiguredBlueprints(),
            'structure' => $this->migrateStructure(),
        ];

        $this->saveMigratedYaml($config, $this->newPath('../pages.yaml'));

        return $this;
    }

    /**
     * Migrate configured blueprints.
     *
     * @return array
     */
    protected function migrateConfiguredBlueprints()
    {
        $blueprints = collect($this->usedBlueprints)->filter()->unique();

        if ($this->files->exists($path = base_path('site/settings/fieldsets'))) {
            $blueprints = collect($this->files->files($path))
                ->reject(function ($blueprint) {
                    return Arr::get(YAML::parse($blueprint->getContents()), 'hide', false);
                })
                ->map
                ->getFilenameWithoutExtension();
        }

        return collect($blueprints)->values()->all();
    }

    /**
     * Migrate structure.
     *
     * @return $this
     */
    protected function migrateStructure()
    {
        if ($home = $this->structure['root']['entry'] ?? false) {
            $tree = collect($this->structure['root']['children'] ?? [])->prepend(['entry' => $home])->all();
        }

        return [
            'root' => true,
            'tree' => $tree ?? [],
        ];
    }

    /**
     * Migrate pages to entries.
     *
     * @return $this
     */
    protected function migratePagesToEntries()
    {
        $this->files->cleanDirectory($this->newPath());

        collect($this->entries)
            ->map(function ($entry) {
                return $this->migrateFieldsetToBlueprint($entry);
            })
            ->map(function ($entry) {
                $this->saveMigratedWithYamlFrontMatter($entry, $this->generateEntryPath($entry));
            });

        return $this;
    }

    /**
     * Generate entry path.
     *
     * @param array $entry
     * @param int $number
     * @return string
     */
    protected function generateEntryPath($entry, $number = 1)
    {
        $appended = $number > 1
            ? "-{$number}"
            : null;

        $path = $this->newPath("{$entry['slug']}{$appended}.md");

        if ($this->files->exists($path)) {
            return $this->generateEntryPath($entry, ++$number);
        }

        return $path;
    }

    /**
     * Migrate fieldset to blueprint.
     *
     * @param array $entry
     * @return array
     */
    protected function migrateFieldsetToBlueprint($entry)
    {
        if (isset($entry['fieldset'])) {
            $entry['blueprint'] = $entry['fieldset'];
        }

        unset($entry['fieldset']);

        return $entry;
    }
}
