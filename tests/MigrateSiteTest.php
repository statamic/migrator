<?php

namespace Tests;

use Statamic\Migrator\Configurator;
use Statamic\Migrator\YAML;

class MigrateSiteTest extends TestCase
{
    protected function paths($key = null)
    {
        $paths = [
            'macros' => resource_path('macros.yaml'),
            'site' => base_path('site'),
            'users' => base_path('users'),
            'blueprints' => resource_path('blueprints'),
            'fieldsets' => resource_path('fieldsets'),
            'trees' => base_path('content/trees'),
            'blog' => base_path('content/collections/blog'),
            'blogCollectionConfig' => base_path('content/collections/blog.yaml'),
            'things' => base_path('content/collections/things'),
            'thingsCollectionConfig' => base_path('content/collections/things.yaml'),
            'favs' => base_path('content/collections/favs'),
            'favsCollectionConfig' => base_path('content/collections/favs.yaml'),
            'favsCollectionTree' => base_path('content/trees/collections/favs.yaml'),
            'pages' => base_path('content/collections/pages'),
            'pagesCollectionConfig' => base_path('content/collections/pages.yaml'),
            'pagesCollectionTree' => base_path('content/trees/collections/pages.yaml'),
            'tags' => base_path('content/taxonomies/tags'),
            'tagsTaxonomyConfig' => base_path('content/taxonomies/tags.yaml'),
            'globals' => base_path('content/globals'),
            'assetContainers' => base_path('content/assets'),
            'contactFormConfig' => resource_path('forms/contact.yaml'),
            'submissions' => storage_path('forms'),
            'views' => resource_path('views'),
            'roles' => resource_path('users/roles.yaml'),
            'groups' => resource_path('users/groups.yaml'),
            'routesFile' => base_path('routes/web.php'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    protected function blueprintsPath($append = null)
    {
        return collect([resource_path('blueprints'), $append])->filter()->implode('/');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->files->copyDirectory(__DIR__.'/Fixtures/site', base_path('site'));
        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('assets'));
        $this->files->copy(__DIR__.'/Fixtures/routes/web.php', $this->paths('routesFile'));
    }

    /** @test */
    public function it_migrates_fieldset_partials()
    {
        $this->assertCount(0, $this->files->files($this->paths('fieldsets')));

        $this->artisan('statamic:migrate:site');

        $this->assertFileExists($this->paths('fieldsets').'/address.yaml');
    }

    /** @test */
    public function it_migrates_collections()
    {
        $this->assertFileNotExists($this->paths('blog'));
        $this->assertFileNotExists($this->paths('blogCollectionConfig'));
        $this->assertFileNotExists($this->paths('things'));
        $this->assertFileNotExists($this->paths('thingsCollectionConfig'));
        $this->assertFileNotExists($this->paths('favs'));
        $this->assertFileNotExists($this->paths('favsCollectionConfig'));
        $this->assertFileNotExists($this->blueprintsPath('collections'));

        $this->artisan('statamic:migrate:site');

        $this->assertFileExists($this->paths('blogCollectionConfig'));
        $this->assertCount(5, $this->files->files($this->paths('blog')));
        $this->assertCount(3, $this->files->files($this->blueprintsPath('collections/blog')));
        $this->assertFileExists($this->paths('thingsCollectionConfig'));
        $this->assertCount(9, $this->files->files($this->paths('things')));
        $this->assertCount(2, $this->files->files($this->blueprintsPath('collections/things')));
        $this->assertFileExists($this->paths('favsCollectionConfig'));
        $this->assertFileExists($this->paths('favsCollectionTree'));
        $this->assertCount(3, $this->files->files($this->paths('favs')));
        $this->assertCount(2, $this->files->files($this->blueprintsPath('collections/favs')));
    }

    /** @test */
    public function it_migrates_pages_to_a_collection()
    {
        $this->assertFileNotExists($this->paths('pagesCollectionConfig'));
        $this->assertFileNotExists($this->paths('pages'));
        $this->assertFileNotExists($this->blueprintsPath('collections'));

        $this->artisan('statamic:migrate:site');

        $this->assertFileExists($this->paths('pagesCollectionConfig'));
        $this->assertFileExists($this->paths('pagesCollectionTree'));
        $this->assertCount(10, $this->files->files($this->paths('pages')));
        $this->assertCount(5, $this->files->files($this->blueprintsPath('collections/pages')));
    }

    /** @test */
    public function it_migrates_taxonomies()
    {
        $this->assertFileNotExists($this->paths('tags'));
        $this->assertFileNotExists($this->paths('tagsTaxonomyConfig'));
        $this->assertFileNotExists($this->blueprintsPath('taxonomies'));

        $this->artisan('statamic:migrate:site');

        $this->assertFileExists($this->paths('tagsTaxonomyConfig'));
        $this->assertCount(2, $this->files->files($this->paths('tags')));
        $this->assertCount(2, $this->files->files($this->blueprintsPath('taxonomies/tags')));
    }

    /** @test */
    public function it_migrates_asset_containers()
    {
        $this->assertCount(0, $this->files->files($this->paths('assetContainers')));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(1, $this->files->files($this->paths('assetContainers')));
        $this->assertFileNotExists($this->blueprintsPath('assets'));
    }

    /** @test */
    public function it_migrates_global_sets()
    {
        $this->assertCount(0, $this->files->files($this->paths('globals')));
        $this->assertFileNotExists($this->blueprintsPath('globals'));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(2, $this->files->files($this->paths('globals')));
        $this->assertCount(1, $this->files->files($this->blueprintsPath('globals')));
    }

    /** @test */
    public function it_migrates_forms()
    {
        $this->assertFileNotExists($this->paths('contactFormConfig'));
        $this->assertFileNotExists($this->blueprintsPath('globals'));
        $this->assertCount(0, $this->files->allFiles($this->paths('submissions')));

        $this->artisan('statamic:migrate:site');

        $this->assertFileExists($this->paths('contactFormConfig'));
        $this->assertFileExists($this->blueprintsPath('forms/contact.yaml'));
        $this->assertCount(2, $this->files->allFiles($this->paths('submissions')));
    }

    /** @test */
    public function it_migrates_users()
    {
        $this->assertCount(0, $this->files->files($this->paths('users')));
        $this->assertFileNotExists($this->blueprintsPath('user.yaml'));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(2, $this->files->files($this->paths('users')));
        $this->assertFileExists($this->blueprintsPath('user.yaml'));
    }

    /** @test */
    public function it_migrates_roles()
    {
        $this->assertFileNotExists($this->paths('roles'));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(2, YAML::parse($this->files->get($this->paths('roles'))));
    }

    /** @test */
    public function it_migrates_groups()
    {
        $this->assertFileNotExists($this->paths('groups'));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(1, YAML::parse($this->files->get($this->paths('groups'))));
    }

    /** @test */
    public function it_migrates_settings()
    {
        $this->assertCount(1, config('statamic.cp.widgets'));

        $this->artisan('statamic:migrate:site');

        Configurator::file('statamic/cp.php')->refresh();

        $this->assertCount(4, config('statamic.cp.widgets'));
    }

    /** @test */
    public function it_migrates_theme()
    {
        $this->assertCount(0, $this->files->allFiles($this->paths('views')));

        $this->artisan('statamic:migrate:site');

        $this->assertCount(33, $this->files->allFiles($this->paths('views')));
    }
}
