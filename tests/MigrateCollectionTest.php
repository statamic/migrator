<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Migrator\YAML;
use Tests\Console\Foundation\InteractsWithConsole;

class MigrateCollectionTest extends TestCase
{
    protected function path($append = null)
    {
        return collect([base_path('content/collections'), $append])->filter()->implode('/');
    }

    /** @test */
    function it_can_migrate_a_collection()
    {
        $this->assertFileNotExists($this->path('blog'));
        $this->assertFileNotExists($this->path('blog.yaml'));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileNotExists($this->path('blog/folder.yaml'));
        $this->assertFileExists($this->path('blog.yaml'));
        $this->assertCount(5, $this->files->files($this->path('blog')));
    }

    /** @test */
    function it_migrates_yaml_config()
    {
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
            'route' => '/blog/{year}/{month}/{day}/{slug}',
        ];

        $this->assertParsedYamlEquals($expected, $this->path('blog.yaml'));
    }

    /** @test */
    function it_migrates_without_a_route()
    {
        $this->files->delete($this->sitePath('settings/routes.yaml'));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlNotHasKey('route', $this->path('blog.yaml'));
    }
}
