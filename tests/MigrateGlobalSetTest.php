<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Migrator\YAML;

class MigrateGlobalSetTest extends TestCase
{
    protected function path()
    {
        return base_path('content/globals/main.yaml');
    }

    protected function originalPath()
    {
        return base_path('site/content/globals/main.yaml');
    }

    private function migrateGlobalSet($globalSet)
    {
        $this->assertFileNotExists($this->path());

        $this->files->put($this->originalPath(), YAML::dump($globalSet));

        $this->artisan('statamic:migrate:global-set', ['handle' => 'main']);

        $this->assertFileExists($this->path());

        return YAML::parse($this->files->get($this->path()));
    }

    /** @test */
    function it_can_migrate_a_global_set()
    {
        $set = $this->migrateGlobalSet([
            'id' => '547c5873-ce9a-4b92-b6b8-a9c785f92fb4',
            'title' => 'Global',
            'fieldset' => 'global',
            'site_title' => 'Frederick\'s Swap Shop',
            'author' => 'Frederick Schwap',
        ]);

        $this->assertEquals($set, [
            'id' => '547c5873-ce9a-4b92-b6b8-a9c785f92fb4',
            'title' => 'Global',
            'blueprint' => 'global',
            'data' => [
                'site_title' => 'Frederick\'s Swap Shop',
                'author' => 'Frederick Schwap',
            ]
        ]);
    }

    /** @test */
    function it_migrates_default_fieldset()
    {
        $set = $this->migrateGlobalSet([
            'id' => '547c5873-ce9a-4b92-b6b8-a9c785f92fb4',
            'title' => 'Global',
            'site_title' => 'Frederick\'s Swap Shop',
            'author' => 'Frederick Schwap',
        ]);

        $this->assertParsedYamlContains(['blueprint' => 'global'], $this->path());
    }
}
