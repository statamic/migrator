<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Migrator\ContentMigrator;

trait MigratesContent
{
    /**
     * Migrate content.
     *
     * @param array $content
     * @param string $fieldset
     * @param bool $addExplicitBlueprint
     * @return array
     */
    protected function migrateContent($content, $fieldset, $addExplicitBlueprint = true)
    {
        if (! $fieldset) {
            return $content;
        }

        return ContentMigrator::usingFieldset($fieldset)
            ->addExplicitBlueprint($addExplicitBlueprint)
            ->migrateContent($content);
    }
}
