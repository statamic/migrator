<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Tests\Console\Foundation\InteractsWithConsole;

class MigrateSiteTest extends TestCase
{
    protected function paths($key = null)
    {
        $paths = [
            'site' => base_path('site'),
            'users' => base_path('users'),
            'blueprints' => resource_path('blueprints'),
            'pages' => base_path('content/collections/pages'),
            'pagesCollectionConfig' => base_path('content/collections/pages.yaml'),
            'pagesStructureConfig' => base_path('content/structures/pages.yaml'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->files->copyDirectory(__DIR__.'/Fixtures/site', base_path('site'));
    }

    /** @test */
    function it_migrates_fieldsets_to_blueprints()
    {
        $this->assertCount(0, $this->files->files($this->paths('blueprints')));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(2, $this->files->files($this->paths('blueprints')));
    }

    /** @test */
    function it_migrates_pages_to_a_collection_with_structure()
    {
        $this->assertFileNotExists($this->paths('pages'));
        $this->assertFileNotExists($this->paths('pagesCollectionConfig'));
        $this->assertFileNotExists($this->paths('pagesStructureConfig'));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(9, $this->files->files($this->paths('pages')));
        $this->assertFileExists($this->paths('pagesStructureConfig'));
        $this->assertFileExists($this->paths('pagesCollectionConfig'));
    }

    /** @test */
    function it_migrates_users()
    {
        $this->assertCount(0, $this->files->files($this->paths('users')));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(2, $this->files->files($this->paths('users')));
    }
}
