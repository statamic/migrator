<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Migrator\YAML;
use Tests\Console\Foundation\InteractsWithConsole;

class MigrateCollectionTest extends TestCase
{
    protected function paths()
    {
        return [
            base_path('content/collections'),
            base_path('site/settings'),
        ];
    }

    protected function collectionsPath($append = null)
    {
        return collect([base_path('content/collections'), $append])->filter()->implode('/');
    }

    protected function settingsPath($append = null)
    {
        return collect([base_path('site/settings'), $append])->filter()->implode('/');
    }

    /** @test */
    function it_can_migrate_a_collection()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/site/content/collections/blog', $this->collectionsPath('blog'));

        $this->assertFileExists($this->collectionsPath('blog/folder.yaml'));
        $this->assertFileNotExists($this->collectionsPath('blog.yaml'));
        $this->assertCount(6, $this->files->files($this->collectionsPath('blog')));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileNotExists($this->collectionsPath('blog/folder.yaml'));
        $this->assertFileExists($this->collectionsPath('blog.yaml'));
        $this->assertCount(5, $this->files->files($this->collectionsPath('blog')));
    }

    /** @test */
    function it_migrates_yaml_config()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/site/content/collections/blog', $this->collectionsPath('blog'));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $expected = [
            'order' => 'date',
            'blueprints' => [
                'post',
            ],
            'template' => 'blog/post',
            'seo' => [
                'description' => '@seo:content'
            ],
            'route' => '/blog/{slug}',
        ];

        $this->assertParsedYamlEquals($expected, $this->collectionsPath('blog.yaml'));
    }

    /** @test */
    function it_migrates_route_from_settings_file_in_site()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/site/content/collections/blog', $this->collectionsPath('blog'));
        $this->files->copy(__DIR__.'/Fixtures/site/settings/routes.yaml', $this->settingsPath('routes.yaml'));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlContains(['route' => '/blog/{year}/{month}/{day}/{slug}'], $this->collectionsPath('blog.yaml'));
    }
}
