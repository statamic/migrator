<?php

namespace Statamic\Migrator;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Migrator\YAML;
use Statamic\Migrator\Exceptions\AlreadyExistsException;

class PagesMigrator extends Migrator
{
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
            ->copyDirectoryFromSiteToNewPath("content/pages")
            ->parseTree()
            ->createStructure()
            ->createYamlConfig()
            ->migratePagesToEntries();
    }

    /**
     * Specify unique paths that shouldn't be overwritten.
     *
     * @return array
     */
    protected function uniquePaths()
    {
        return [
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
        $this->parsePageFolder($this->newPath());

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
        $page = YAML::parse($this->files->get("{$folder}/index.md"));

        $page['slug'] = $key === 'root'
            ? Str::slug($page['title'])
            : preg_replace('/.*\/[0-9]*\.([^\/]*)$/', '$1', $folder);

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
     * Create structure.
     *
     * @return $this
     */
    protected function createStructure()
    {
        $config = [
            'title' => 'Pages',
            'expects_root' => true,
            'root' => $this->structure['root']['entry'],
            'tree' => $this->structure['root']['children'],
        ];

        $this->files->put($this->newPath('../../structures/pages.yaml'), YAML::dump($config));

        return $this;
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
            'structure' => 'pages',
        ];

        $this->files->put($this->newPath('../pages.yaml'), YAML::dump($config));

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
                $this->files->put($this->generateEntryPath($entry), $this->dumpEntryToMarkdown($entry));
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

    /**
     * Dump entry to markdown.
     *
     * @param array $entry
     * @return string
     */
    protected function dumpEntryToMarkdown($entry)
    {
        return isset($entry['content'])
            ? YAML::dumpFrontMatter(collect($entry)->except('content')->all()) . $entry['content']
            : YAML::dump($entry);
    }
}
