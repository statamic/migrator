<?php

namespace Statamic\Migrator;

class SettingsMigrator extends Migrator
{
    /**
     * Perform migration.
     */
    public function migrate()
    {
        if ($this->handle) {
            return $this->migrateSingle();
        }

        $this
            ->migrateAssets()
            ->migrateCaching()
            ->migrateCp()
            ->migrateDebug()
            ->migrateEmail()
            ->migrateRoutes()
            ->migrateRoutes()
            ->migrateSearch()
            ->migrateSystem()
            ->migrateTheming()
            ->migrateUsers();
    }

    /**
     * Perform migration on single settings file.
     *
     * @return $this
     */
    protected function migrateSingle()
    {
        $migrateMethod = 'migrate' . ucfirst($this->handle);

        return $this->{$migrateMethod}();
    }
}
