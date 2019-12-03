<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Migrator\YAML;

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
            'blueprints' => [
                'post',
            ],
            'template' => 'blog/post',
            'seo' => [
                'description' => '@seo:content'
            ],
            'route' => '/blog/{year}/{month}/{day}/{slug}',
            'taxonomies' => [
                'tags',
            ],
            'date' => true,
            'date_behavior' => [
                'past' => 'public',
                'future' => 'unlisted',
            ],
            'sort_dir' => 'desc',
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
    function it_migrates_a_draft_entry()
    {
        $draftPath = 'blog/_2017-01-19.paperwork-and-snowshoeing.md';
        $path = 'blog/2017-01-19.paperwork-and-snowshoeing.md';

        $this->assertFileExists($this->sitePath("content/collections/{$draftPath}"));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileNotExists($this->path($draftPath));
        $this->assertFileExists($this->path($path));
        $this->assertParsedYamlContains(['published' => false], $this->path($path));
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

        $this->assertContainsIgnoringLineEndings($expected, $this->files->get($this->path('blog/2017-07-31.fire-fire-looking-forward-to-hearing-from-you.md')));
    }

    /** @test */
    function it_can_migrate_multiple_taxonomies_onto_collection()
    {
        $path = $this->sitePath('content/collections/blog/2017-07-31.fire-fire-looking-forward-to-hearing-from-you.md');
        $entry = $this->files->get($path);

        $entry = str_replace('---', <<<EOT
colours:
  - red
  - blue
---
EOT
        , $entry);

        $this->files->put($path, $entry);

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlContains(['taxonomies' => ['colours', 'tags']], $this->path('blog.yaml'));
    }

    /** @test */
    function it_will_not_migrate_taxonomies_if_none_are_referenced()
    {
        collect($this->files->allFiles($this->sitePath('content/collections/blog')))->each(function ($entry) {
            $this->files->put($entry->getPathname(), str_replace('tags:', 'not_tags:', $entry->getContents()));
        });

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlNotHasKey('taxonomies', $this->path('blog.yaml'));
    }

    /** @test */
    function it_can_migrate_if_taxonomies_are_missing()
    {
        $this->files->deleteDirectory($this->sitePath('content/taxonomies'));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlNotHasKey('taxonomies', $this->path('blog.yaml'));
    }

    /** @test */
    function it_will_not_migrate_date_settings_if_none_are_referenced()
    {
        $path = $this->sitePath('content/collections/blog/folder.yaml');

        $this->files->put($path, str_replace('order:', 'not_order:', $this->files->get($path)));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlNotHasKey('date', $this->path('blog.yaml'));
        $this->assertParsedYamlNotHasKey('date_behavior', $this->path('blog.yaml'));
        $this->assertParsedYamlNotHasKey('sort_dir', $this->path('blog.yaml'));
    }

    /** @test */
    function it_migrates_number_ordered_collection()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'favs']);

        $expectedConfig = [
            'blueprints' => [
                'post',
            ],
            'template' => 'blog/post',
            'orderable' => true,
            'entry_order' => [
                '93c5ea5e-581d-4074-af70-1eeae01d7880',
                '82f60ba2-6c16-4889-8420-d1c8e7adfa3d',
            ],
        ];

        $this->assertParsedYamlEquals($expectedConfig, $this->path('favs.yaml'));
        $this->assertFileExists($this->path('favs/red-shirt.md'));
        $this->assertFileExists($this->path('favs/blue-shirt.md'));
    }
}
