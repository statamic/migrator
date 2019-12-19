<?php

namespace Statamic\Migrator;

use Statamic\Support\Str;
use Statamic\Facades\Path;

class ThemeMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\PreparesPathFolder;

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
            ->migrateTemplates();
    }

    /**
     * Specify unique paths that shouldn't be overwritten.
     *
     * @return array
     */
    protected function uniquePaths()
    {
        return $this->templates->keys()->all();
    }

    /**
     * Parse theme.
     *
     * @return $this
     */
    protected function parseTheme()
    {
        $this->templates = collect()
            ->merge($this->files->allFiles($this->sitePath("themes/{$this->handle}/layouts")))
            ->merge($this->files->allFiles($this->sitePath("themes/{$this->handle}/partials")))
            ->merge($this->files->allFiles($this->sitePath("themes/{$this->handle}/templates")))
            ->filter(function ($template) {
                return Str::endsWith($template->getFilename(), ['.html', '.blade.php']);
            });

        return $this;
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
    }

    /**
     * Migrate path.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $template
     * @return string
     */
    protected function migratePath($template)
    {
        $originalPath = Path::resolve($template->getPath());
        $relativePath = $this->convertExtension($template->getRelativePathname());

        if (Str::contains($originalPath, "themes/{$this->handle}/layouts")) {
            $relativePath = 'layouts/' . $relativePath;
        } elseif (Str::contains($originalPath, "themes/{$this->handle}/partials")) {
            $relativePath = 'partials/' . $relativePath;
        }

        $absolutePath = $this->newPath($relativePath);

        $this->prepareFolder($absolutePath);

        return $absolutePath;
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
}
