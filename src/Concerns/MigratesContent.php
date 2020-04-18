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
     * @return array
     */
    protected function migrateContent($content, $fieldset)
    {
        // TODO: Throw warning?
        if (! $fieldset) {
            return $content;
        }

        return ContentMigrator::usingFieldset($fieldset)->migrateContent($content);
    }
}
