<?php

namespace Tests;

use Statamic\Facades\Path;
use Statamic\Migrator\YAML;

class MigrateGlobalSetTest extends TestCase
{
    protected function paths()
    {
        return [
            base_path('content/globals/main.yaml'),
            resource_path('blueprints'),
        ];
    }

    protected function originalPath()
    {
        return Path::tidy(base_path('site/content/globals/main.yaml'));
    }

    protected function newPath()
    {
        return Path::tidy(base_path('content/globals/main.yaml'));
    }

    protected function blueprintPath()
    {
        return Path::tidy(resource_path('blueprints/globals/main.yaml'));
    }

    private function migrateGlobalSet($globalSet)
    {
        $this->assertFileNotExists($this->newPath());

        $this->files->put($this->originalPath(), YAML::dump($globalSet));

        $this->artisan('statamic:migrate:global-set', ['handle' => 'main']);

        $this->assertFileExists($this->newPath());

        return YAML::parse($this->files->get($this->newPath()));
    }

    /** @test */
    public function it_can_migrate_a_global_set()
    {
        $this->assertFileNotExists($this->newPath());
        $this->assertFileNotExists($this->blueprintPath());

        $set = $this->migrateGlobalSet([
            'id' => '547c5873-ce9a-4b92-b6b8-a9c785f92fb4',
            'title' => 'Global',
            'fieldset' => 'globals',
            'site_title' => 'Frederick\'s Swap Shop',
            'author' => 'Frederick Schwap',
            'fav_colour' => 'colours/red', // this is a taxonomy field, with a value that needs to be migrated
        ]);

        $this->assertEquals($set, [
            'title' => 'Global',
            'data' => [
                'site_title' => 'Frederick\'s Swap Shop',
                'author' => 'Frederick Schwap',
                'fav_colour' => 'red',
            ],
        ]);

        $this->assertFileExists($this->newPath());
        $this->assertFileExists($this->blueprintPath());
    }

    /** @test */
    public function it_can_implicitly_migrate_globals_blueprint()
    {
        $this->assertFileNotExists($this->blueprintPath());

        $set = $this->migrateGlobalSet([
            'id' => '547c5873-ce9a-4b92-b6b8-a9c785f92fb4',
            'title' => 'Global',
            'site_title' => 'Frederick\'s Swap Shop',
            'author' => 'Frederick Schwap',
            // 'fieldset' => 'globals', // If this is not explicitly set, fall back to migrating `globals` fieldset
        ]);

        $this->assertFileExists($this->blueprintPath());
    }

    /** @test */
    public function it_migrates_without_fieldset_when_one_does_not_exist()
    {
        $this->files->delete(base_path('site/settings/fieldsets/globals.yaml'));

        $this->assertFileNotExists($this->newPath());
        $this->assertFileNotExists($this->blueprintPath());

        $set = $this->migrateGlobalSet([
            'title' => 'Global',
            'site_title' => 'Frederick\'s Swap Shop',
            'author' => 'Frederick Schwap',
        ]);

        $this->assertFileExists($this->newPath());
        $this->assertFileNotExists($this->blueprintPath());
    }
}
