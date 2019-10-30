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

    /** @test */
    function it_migrates_entry()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $path = $this->path('blog/2017-09-28.what-i-did-last-summer.md');

        $this->assertParsedYamlHasKey('id', $path);
        $this->assertParsedYamlContains(['blueprint' => 'long_form'], $path);
        $this->assertParsedYamlNotHasKey('fieldset', $path);
    }

    /** @test */
    function it_migrates_entry_content_as_document_content()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $expected = <<<EOT
id: f5c18e4c-4d51-4fc6-ab52-b7afe5116b3a
---
Let me first explain myself. I am not a brave person by nature.
EOT;

        $this->assertContains($expected, $this->files->get($this->path('blog/2017-07-31.fire-fire-looking-forward-to-hearing-from-you.md')));
    }
}
