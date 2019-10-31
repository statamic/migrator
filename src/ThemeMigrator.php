<?php

namespace Statamic\Migrator;

use statamic\Support\Str;

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
        $this->templates = collect($this->files->allFiles($this->sitePath("themes/{$this->handle}/templates")))
            ->filter(function ($template) {
                return Str::endsWith($template->getFilename(), ['.html', '.blade.php']);
            })
            ->keyBy(function ($template) {
                return $this->newPath($this->convertExtension($template->getRelativePathname()));
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
        $this->templates->each(function ($template, $path) {
            $this->files->put($this->migratePath($path), $this->migrateTemplate($template->getContents()));
        });
    }

    /**
     * Migrate path.
     *
     * @param string $path
     * @return string
     */
    protected function migratePath($path)
    {
        $this->prepareFolder($path);

        return $this->convertExtension($path);
    }

    /**
     * Convert extension.
     *
     * @param string $path
     * @return string
     */
    protected function convertExtension($path)
    {
        if (Str::endsWith($path, '.antlers.html')) {
            return $path;
        }

        return str_replace('.html', '.antlers.html', $path);
    }

    /**
     * Migrate template.
     *
     * @param string $template
     * @return string
     */
    protected function migrateTemplate($template)
    {
        return $template;
    }
}
