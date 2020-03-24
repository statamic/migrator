<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Migrator\YAML;
use Statamic\Migrator\Configurator;
use Illuminate\Filesystem\Filesystem;

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
            'tags' => base_path('content/taxonomies/tags'),
            'tagsTaxonomyConfig' => base_path('content/taxonomies/tags.yaml'),
            'globals' => base_path('content/globals'),
            'assetContainers' => base_path('content/assets'),
            'views' => resource_path('views'),
            'roles' => resource_path('users/roles.yaml'),
            'groups' => resource_path('users/groups.yaml'),
            'routesFile' => base_path('routes/web.php'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->files->copyDirectory(__DIR__.'/Fixtures/site', base_path('site'));
        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('assets'));
        $this->files->copy(__DIR__ . '/Fixtures/routes/web.php', $this->paths('routesFile'));
    }

    /** @test */
    function it_migrates_fieldsets_to_blueprints()
    {
        $this->assertCount(0, $this->files->files($this->paths('blueprints')));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(13, $this->files->files($this->paths('blueprints')));
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
    function it_migrates_pages_to_a_collection()
    {
        $this->assertFileNotExists($this->paths('pagesCollectionConfig'));
        $this->assertFileNotExists($this->paths('pages'));

        $this->artisan('statamic:migrate:site');

        $this->assertFileExists($this->paths('pagesCollectionConfig'));
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

    /** @test */
    function it_migrates_roles()
    {
        $this->assertFileNotExists($this->paths('roles'));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(2, YAML::parse($this->files->get($this->paths('roles'))));
    }

    /** @test */
    function it_migrates_groups()
    {
        $this->assertFileNotExists($this->paths('groups'));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(1, YAML::parse($this->files->get($this->paths('groups'))));
    }

    /** @test */
    function it_migrates_settings()
    {
        $this->assertCount(1, config('statamic.cp.widgets'));

        $this->artisan('statamic:migrate:site');

        Configurator::file('statamic/cp.php')->refresh();

        $this->assertCount(4, config('statamic.cp.widgets'));
    }

    /** @test */
    function it_migrates_theme()
    {
        $this->assertCount(0, $this->files->allFiles($this->paths('views')));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(33, $this->files->allFiles($this->paths('views')));
    }
}
