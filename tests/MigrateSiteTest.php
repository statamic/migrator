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
            'blog' => base_path('content/collections/blog'),
            'blogCollectionConfig' => base_path('content/collections/blog.yaml'),
            'things' => base_path('content/collections/things'),
            'thingsCollectionConfig' => base_path('content/collections/things.yaml'),
            'pages' => base_path('content/collections/pages'),
            'pagesCollectionConfig' => base_path('content/collections/pages.yaml'),
            'pagesStructureConfig' => base_path('content/structures/pages.yaml'),
            'tags' => base_path('content/taxonomies/tags'),
            'tagsTaxonomyConfig' => base_path('content/taxonomies/tags.yaml'),
            'globals' => base_path('content/globals'),
            'assetContainers' => base_path('content/assets'),
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

        $this->assertCount(12, $this->files->files($this->paths('blueprints')));
    }

    /** @test */
    function it_migrates_collections()
    {
        $this->assertFileNotExists($this->paths('blog'));
        $this->assertFileNotExists($this->paths('blogCollectionConfig'));
        $this->assertFileNotExists($this->paths('things'));
        $this->assertFileNotExists($this->paths('thingsCollectionConfig'));

        $this->artisan('statamic:migrate:site');

        $this->assertFileExists($this->paths('blogCollectionConfig'));
        $this->assertCount(5, $this->files->files($this->paths('blog')));
        $this->assertFileExists($this->paths('thingsCollectionConfig'));
        $this->assertCount(9, $this->files->files($this->paths('things')));
    }

    /** @test */
    function it_migrates_pages_to_a_collection_with_structure()
    {
        $this->assertFileNotExists($this->paths('pagesCollectionConfig'));
        $this->assertFileNotExists($this->paths('pagesStructureConfig'));
        $this->assertFileNotExists($this->paths('pages'));

        $this->artisan('statamic:migrate:site');

        $this->assertFileExists($this->paths('pagesCollectionConfig'));
        $this->assertFileExists($this->paths('pagesStructureConfig'));
        $this->assertCount(10, $this->files->files($this->paths('pages')));
    }

    /** @test */
    function it_migrates_taxonomies()
    {
        $this->assertFileNotExists($this->paths('tags'));
        $this->assertFileNotExists($this->paths('tagsTaxonomyConfig'));

        $this->artisan('statamic:migrate:site');

        $this->assertFileExists($this->paths('tagsTaxonomyConfig'));
        $this->assertCount(2, $this->files->files($this->paths('tags')));
    }

    /** @test */
    function it_migrates_asset_containers()
    {
        $this->assertCount(0, $this->files->files($this->paths('assetContainers')));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(1, $this->files->files($this->paths('assetContainers')));
    }

    /** @test */
    function it_migrates_global_sets()
    {
        $this->assertCount(0, $this->files->files($this->paths('globals')));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(2, $this->files->files($this->paths('globals')));
    }

    /** @test */
    function it_migrates_users()
    {
        $this->assertCount(0, $this->files->files($this->paths('users')));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(2, $this->files->files($this->paths('users')));
    }
}
