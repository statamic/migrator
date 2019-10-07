<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Migrator\YAML;
use Tests\Console\Foundation\InteractsWithConsole;

class MigrateGlobalSetTest extends TestCase
{
    protected function path($append = null)
    {
        return collect([base_path('site/content/globals'), $append])->filter()->implode('/');
    }

    private function migrateGlobalSet($globalSet)
    {
        $this->assertFileNotExists($this->path('test.yaml'));

        $this->files->put($this->path('test.yaml'), YAML::dump($globalSet));

        $this->artisan('statamic:migrate:global-set', ['handle' => 'test']);

        $this->assertFileExists($this->path('test.yaml'));

        return YAML::parse($this->files->get($this->path('test.yaml')));
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

        $this->assertParsedYamlContains(['blueprint' => 'global'], $set);
    }
}
