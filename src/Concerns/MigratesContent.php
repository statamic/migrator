<?php

namespace Statamic\Migrator\Concerns;

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
        $content = $this->migrateFieldsetToBlueprint($content);

        return $content;
    }

    /**
     * Migrate fieldset to blueprint.
     *
     * @param array $entry
     * @return array
     */
    protected function migrateFieldsetToBlueprint($content)
    {
        if (isset($content['fieldset'])) {
            $content['blueprint'] = $content['fieldset'];
        }

        unset($content['fieldset']);

        return $content;
    }
}
