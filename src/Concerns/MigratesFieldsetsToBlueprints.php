<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Migrator\Exceptions\MigratorWarningsException;
use Statamic\Migrator\FieldsetMigrator;
use Statamic\Migrator\YAML;

trait MigratesFieldsetsToBlueprints
{
    protected $migratableFieldsets = [];

    /**
     * Add fieldset to be migrated to a blueprint at the end of a migration.
     *
     * @param  string  $handle
     * @return $this
     */
    protected function addMigratableFieldset($handle)
    {
        $this->migratableFieldsets[] = $handle;

        return $this;
    }

    /**
     * Get migratable fieldsets.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getMigratableFieldsets()
    {
        return collect($this->migratableFieldsets)
            ->filter()
            ->unique()
            ->reject(function ($handle) {
                return $this->isNonExistentFieldset($handle) || $this->isNonExistentDefaultFieldset($handle);
            });
    }

    /**
     * Migrate queued migratable fieldsets to blueprints.
     *
     * @param  string  $blueprintsFolder
     * @return $this
     */
    protected function migrateFieldsetsToBlueprints($blueprintsFolder)
    {
        $this->getMigratableFieldsets()->each(function ($handle) use ($blueprintsFolder) {
            try {
                $this->migrateFieldsetToBlueprint($blueprintsFolder, $handle);
            } catch (MigratorWarningsException $exception) {
                $this->mergeFromWarningsException($exception);
            }
        });

        $this->ensureDefaultBlueprint($blueprintsFolder);

        return $this;
    }

    /**
     * Migrate fieldset to blueprint.
     *
     * @param  string  $blueprintsFolder
     * @param  string  $originalHandle
     * @param  string|null  $newHandle
     * @return $this
     */
    protected function migrateFieldsetToBlueprint($blueprintsFolder, $originalHandle, $newHandle = null)
    {
        try {
            FieldsetMigrator::asBlueprint($blueprintsFolder, $originalHandle, $newHandle)
                ->overwrite($this->overwrite)
                ->migrate();
        } catch (NotFoundException $exception) {
            $this->addWarning($exception->getMessage());
        }

        return $this;
    }

    /**
     * Checks if fieldset is non-existent.
     *
     * @param  string  $handle
     * @return bool
     */
    protected function isNonExistentFieldset($handle)
    {
        return ! $this->files->exists($this->sitePath("settings/fieldsets/{$handle}.yaml"));
    }

    /**
     * Checks if fieldset is a non-existent default fieldset from settings.
     *
     * This is important because if a default fieldset is referenced but does not exist, the content
     * referencing it should be using the default fieldset in core (which is just title, slug, and content).
     *
     * @param  string  $handle
     * @return bool
     */
    protected function isNonExistentDefaultFieldset($handle)
    {
        $defaultFieldsets = $this->getDefaultFieldsets()->filter();

        if (! $defaultFieldsets->contains($handle)) {
            return false;
        }

        return $defaultFieldsets
            ->filter(function ($handle) {
                return $this->files->exists($this->sitePath("settings/fieldsets/{$handle}.yaml"));
            })
            ->isEmpty();
    }

    /**
     * Get default fieldsets.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getDefaultFieldsets()
    {
        // TODO: Figure out how to fix php 7.2 trait collision using trait aliasing?
        $container = new class
        {
            use GetsSettings;

            public function getDefaultFieldsets()
            {
                return collect([
                    $this->getSetting('theming.default_fieldset'),
                    $this->getSetting('theming.default_page_fieldset'),
                    $this->getSetting('theming.default_entry_fieldset'),
                    $this->getSetting('theming.default_term_fieldset'),
                    $this->getSetting('theming.default_asset_fieldset'),
                ]);
            }
        };

        return $container->getDefaultFieldsets();
    }

    /**
     * Ensure default blueprint.
     *
     * @param  string  $blueprintsFolder
     */
    protected function ensureDefaultBlueprint($blueprintsFolder)
    {
        $defaultBlueprintPath = resource_path("blueprints/{$blueprintsFolder}/default.yaml");

        if (! $this->files->exists($defaultBlueprintPath)) {
            return $this->copyDefaultBlueprint($blueprintsFolder);
        }

        $blueprint = collect(YAML::parse($this->files->get($defaultBlueprintPath)));

        if ($blueprint->get('order') == 1) {
            return;
        }

        $blueprint = $blueprint->put('order', 1)->all();

        $this->files->put($defaultBlueprintPath, YAML::dump($blueprint));
    }

    /**
     * Copy default blueprint.
     *
     * @param  string  $blueprintsFolder
     */
    protected function copyDefaultBlueprint($blueprintsFolder)
    {
        $folder = resource_path("blueprints/{$blueprintsFolder}");

        if (! $this->files->exists($folder)) {
            $this->files->makeDirectory($folder, 0755, true);
        }

        $stub = __DIR__.'/../../resources/blueprints/default.yaml';
        $path = $folder.'/default.yaml';

        $this->files->copy($stub, $path);
    }
}
