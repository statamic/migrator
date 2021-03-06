<?php

namespace Tests;

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
        return base_path('site/content/globals/main.yaml');
    }

    protected function newPath()
    {
        return base_path('content/globals/main.yaml');
    }

    protected function blueprintPath()
    {
        return resource_path('blueprints/globals/main.yaml');
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
        ]);

        $this->assertEquals($set, [
            'title' => 'Global',
            'data' => [
                'site_title' => 'Frederick\'s Swap Shop',
                'author' => 'Frederick Schwap',
            ],
        ]);

        $this->assertFileExists($this->newPath());
        $this->assertFileExists($this->blueprintPath());
    }

    /** @test */
    public function it_migrates_without_fieldset()
    {
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
