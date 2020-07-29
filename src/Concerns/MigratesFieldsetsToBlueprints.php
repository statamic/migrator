<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Migrator\FieldsetMigrator;

trait MigratesFieldsetsToBlueprints
{
    protected $migratableFieldsets = [];

    /**
     * Add fieldset to be migrated to a blueprint at the end of a migration.
     *
     * @param string $handle
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
                return $this->isNonExistentDefaultFieldset($handle);
            });
    }

    /**
     * Migrate fieldsets to blueprints.
     *
     * @param string $blueprintsFolder
     * @return $this
     */
    protected function migrateFieldsetsToBlueprints($blueprintsFolder)
    {
        $this->getMigratableFieldsets()->each(function ($handle) use ($blueprintsFolder) {
            try {
                FieldsetMigrator::asBlueprint($handle, $blueprintsFolder)->migrate();
            } catch (NotFoundException $exception) {
                $this->addWarning($exception->getMessage());
            }
        });

        return $this;
    }

    /**
     * Checks if fieldset is a non-existent default fieldset from settings.
     *
     * This is important because if a default fieldset is referenced but does not exist, the content
     * referencing it should be using the default fieldset in core (which is just title, slug, and content).
     *
     * @param string $handle
     * @return bool
     */
    protected function isNonExistentDefaultFieldset($handle)
    {
        $defaultFieldsets = $this->getDefaultFieldsets();

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
        $container = new class {
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
}
