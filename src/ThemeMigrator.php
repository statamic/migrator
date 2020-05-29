<?php

namespace Statamic\Migrator;

use Statamic\Facades\Path;
use Statamic\Migrator\Exceptions\NotFoundException;
use Statamic\Support\Str;

class ThemeMigrator extends Migrator
{
    use Concerns\GetsSettings,
        Concerns\MigratesFile,
        Concerns\PreparesPathFolder,
        Concerns\ThrowsFinalWarnings;

    protected $templates;

    /**
     * Perform migration.
     *
     * @param string $handle
     */
    public function migrate()
    {
        $this
            ->setNewPath(resource_path('views'))
            ->parseTheme()
            ->validateUnique()
            ->migrateTemplates()
            ->migrateMacros()
            ->throwFinalWarnings();
    }

    /**
     * Specify unique paths that shouldn't be overwritten.
     *
     * @return array
     */
    protected function uniquePaths()
    {
        $templates = $this->templates->map(function ($template) {
            return $this->migratePath($template, false);
        })->all();

        $misc = [
            resource_path('macros.yaml'),
        ];

        return array_merge($templates, $misc);
    }

    /**
     * Parse theme.
     *
     * @return $this
     */
    protected function parseTheme()
    {
        if (! $this->files->exists($path = $this->sitePath("themes/{$this->handle}"))) {
            throw new NotFoundException('Theme folder cannot be found at path [path].', $path);
        }

        $this->templates = collect()
            ->merge($this->getThemeFiles('layouts'))
            ->merge($this->getThemeFiles('partials'))
            ->merge($this->getThemeFiles('templates'))
            ->filter(function ($template) {
                return Str::endsWith($template->getFilename(), ['.html', '.blade.php']);
            });

        return $this;
    }

    /**
     * Get theme files from subfolder.
     *
     * @param string $subFolder
     * @return array
     */
    protected function getThemeFiles($subFolder)
    {
        $path = $this->sitePath("themes/{$this->handle}/{$subFolder}");

        return $this->files->exists($path)
            ? $this->files->allFiles($path)
            : [];
    }

    /**
     * Migrate templates.
     *
     * @return $this
     */
    protected function migrateTemplates()
    {
        $this->templates->each(function ($template) {
            $this->files->put($this->migratePath($template), $this->migrateTemplate($template));
        });

        $this->addWarning(
            "Your [{$this->handle}] theme templates have been migrated to [resources/views].",
            'It\'s worth noting that Antlers templating has undergone a number of changes.  Many of these changes are opinionated and will need your attention (please refer to [https://statamic.dev/upgrade-guide] for an overview of the most breaking changes).  Your theme\'s front end assets and build pipelines will also need to be manually migrated.  We recommend checking out Laravel Mix if you are building your assets (Mix comes pre-installed into your v3 apps, and documentation is available at [https://laravel.com/docs/mix]).'
        );

        return $this;
    }

    /**
     * Migrate path.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $template
     * @param bool $prepareFolder
     * @return string
     */
    protected function migratePath($template, $prepareFolder = true)
    {
        $originalPath = Path::resolve($template->getPath());
        $relativePath = $this->convertExtension($template->getRelativePathname());

        if (Str::contains($originalPath, "themes/{$this->handle}/layouts")) {
            $relativePath = 'layouts/'.$this->migrateLayoutFilename($relativePath);
        } elseif (Str::contains($originalPath, "themes/{$this->handle}/partials")) {
            $relativePath = 'partials/'.$relativePath;
        }

        $absolutePath = $this->newPath($relativePath);

        if ($prepareFolder) {
            $this->prepareFolder($absolutePath);
        }

        return $absolutePath;
    }

    /**
     * Attempt changing `default` layout to `layout`, if possible.
     *
     * @param string $filename
     * @return string
     */
    protected function migrateLayoutFilename($filename)
    {
        if ($this->files->exists($this->sitePath("themes/{$this->handle}/layouts/layout.html"))) {
            return $filename;
        }

        $defaultLayout = $this->getSetting('theming.default_layout', 'default');

        $parts = collect(explode('.', $filename));

        if ($parts->get(0) === $defaultLayout) {
            $parts->put(0, 'layout');
        }

        return $parts->implode('.');
    }

    /**
     * Convert extension.
     *
     * @param string $path
     * @return string
     */
    protected function convertExtension($path)
    {
        if (Str::endsWith($path, ['.antlers.html', '.blade.php'])) {
            return $path;
        }

        return str_replace('.html', '.antlers.html', $path);
    }

    /**
     * Migrate template.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $template
     * @return string
     */
    protected function migrateTemplate($template)
    {
        if (Str::endsWith($template->getPathname(), '.blade.php')) {
            return $this->migrateBladeTemplate($template->getContents());
        }

        return $this->migrateAntlersTemplate($template->getContents());
    }

    /**
     * Migrate antlers template.
     *
     * @param string $template
     * @return string
     */
    protected function migrateAntlersTemplate($template)
    {
        // Migrate `theme:partial` tags.
        $template = preg_replace('/theme:partial.*src=[\'"]([^\'"]*)[\'"]/mU', 'partial:$1', $template);

        return $template;
    }

    /**
     * Migrate blade template.
     *
     * @param string $template
     * @return string
     */
    protected function migrateBladeTemplate($template)
    {
        // Migrate global `modify()` function calls.
        $template = preg_replace('/modify\((.*)\)->/mU', '\Statamic\Modifiers\Modify::value($1)->', $template);

        return $template;
    }

    /**
     * Migrate macros.
     *
     * @return $this
     */
    protected function migrateMacros()
    {
        if ($this->files->exists($path = $this->sitePath("themes/{$this->handle}/settings/macros.yaml"))) {
            $this->files->copy($path, resource_path('macros.yaml'));
        }

        return $this;
    }
}
