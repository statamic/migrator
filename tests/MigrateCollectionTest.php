<?php

namespace Tests;

use Statamic\Migrator\YAML;

class MigrateCollectionTest extends TestCase
{
    protected function paths()
    {
        return [
            'collections' => base_path('content/collections'),
            'blueprints' => resource_path('blueprints/collections'),
        ];
    }

    protected function collectionsPath($append = null)
    {
        return collect([base_path('content/collections'), $append])->filter()->implode('/');
    }

    protected function blueprintsPath($append = null)
    {
        return collect([resource_path('blueprints/collections'), $append])->filter()->implode('/');
    }

    private function migrateCollection($config)
    {
        $path = $this->sitePath('content/collections/test/folder.yaml');

        $this->prepareFolder($path);

        $this->files->put($path, YAML::dump($config));

        $this->artisan('statamic:migrate:collection', ['handle' => 'test']);

        return YAML::parse($this->files->get($this->collectionsPath('test.yaml')));
    }

    /** @test */
    public function it_can_migrate_a_collection()
    {
        $this->assertFileNotExists($this->collectionsPath('blog'));
        $this->assertFileNotExists($this->collectionsPath('blog.yaml'));
        $this->assertFileNotExists($this->blueprintsPath('blog'));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileNotExists($this->collectionsPath('blog/folder.yaml'));
        $this->assertFileExists($this->collectionsPath('blog.yaml'));
        $this->assertCount(5, $this->files->files($this->collectionsPath('blog')));
        $this->assertCount(3, $this->files->files($this->blueprintsPath('blog')));
        $this->assertParsedYamlContains(['order' => 1], $this->blueprintsPath('blog/default.yaml'));
    }

    /** @test */
    public function it_can_migrate_a_custom_default_blueprint()
    {
        $this->files->put($this->prepareFolder($this->blueprintsPath('blog/default.yaml')), YAML::dump([
            'title' => 'Default',
            'custom' => 'stuff',
        ]));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertCount(3, $this->files->files($this->blueprintsPath('blog')));
        $this->assertParsedYamlContains(['custom' => 'stuff', 'order' => 1], $this->blueprintsPath('blog/default.yaml'));
    }

    /** @test */
    public function it_migrates_yaml_config()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $expected = [
            'template' => 'blog/post',
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

        $this->assertParsedYamlEquals($expected, $this->collectionsPath('blog.yaml'));
    }

    /** @test */
    public function it_migrates_without_a_route()
    {
        $this->files->delete($this->sitePath('settings/routes.yaml'));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlNotHasKey('route', $this->collectionsPath('blog.yaml'));
    }

    /** @test */
    public function it_wraps_non_reserved_config_with_inject()
    {
        $collection = $this->migrateCollection([
            'title' => 'Blog',
            'seo' => false,
            'loller' => 'baller',
        ]);

        $expected = [
            'title' => 'Blog',
            'inject' => [
                'seo' => false,
                'loller' => 'baller',
            ],
        ];

        $this->assertEquals($expected, $collection);
    }

    /** @test */
    public function it_migrates_spicey_yaml()
    {
        $collection = $this->migrateCollection([
            'seo' => [
                'description' => '@seo:content',
            ],
        ]);

        $expected = [
            'inject' => [
                'seo' => [
                    'description' => '@seo:content',
                ],
            ],
        ];

        $this->assertEquals($expected, $collection);
    }

    /** @test */
    public function it_migrates_entry()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $path = $this->collectionsPath('blog/2017-09-28.what-i-did-last-summer.md');

        $this->assertParsedYamlHasKey('id', $path);
        $this->assertParsedYamlContains(['blueprint' => 'long_form'], $path);
        $this->assertParsedYamlNotHasKey('fieldset', $path);
    }

    /** @test */
    public function it_migrates_a_draft_entry()
    {
        $draftPath = 'blog/_2017-01-19.paperwork-and-snowshoeing.md';
        $path = 'blog/2017-01-19.paperwork-and-snowshoeing.md';

        $this->assertFileExists($this->sitePath("content/collections/{$draftPath}"));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileNotExists($this->collectionsPath($draftPath));
        $this->assertFileExists($this->collectionsPath($path));
        $this->assertParsedYamlContains(['published' => false], $this->collectionsPath($path));
    }

    /** @test */
    public function it_migrates_entry_content_as_document_content()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $expected = <<<'EOT'
id: f5c18e4c-4d51-4fc6-ab52-b7afe5116b3a
blueprint: post
---
Let me first explain myself. I am not a brave person by nature.
EOT;

        $this->assertContainsIgnoringLineEndings($expected, $this->files->get($this->collectionsPath('blog/2017-07-31.fire-fire-looking-forward-to-hearing-from-you.md')));
    }

    /** @test */
    public function it_can_migrate_multiple_taxonomies_onto_collection()
    {
        $path = $this->sitePath('content/collections/blog/2017-07-31.fire-fire-looking-forward-to-hearing-from-you.md');
        $entry = $this->files->get($path);

        $entry = str_replace('---', <<<'EOT'
colours:
  - red
  - blue
---
EOT
        , $entry);

        $this->files->put($path, $entry);

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlContains(['taxonomies' => ['colours', 'tags']], $this->collectionsPath('blog.yaml'));
    }

    /** @test */
    public function it_will_not_migrate_taxonomies_if_none_are_referenced()
    {
        collect($this->files->allFiles($this->sitePath('content/collections/blog')))->each(function ($entry) {
            $this->files->put($entry->getPathname(), str_replace('tags:', 'not_tags:', $entry->getContents()));
        });

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlNotHasKey('taxonomies', $this->collectionsPath('blog.yaml'));
    }

    /** @test */
    public function it_can_migrate_if_taxonomies_are_missing()
    {
        $this->files->deleteDirectory($this->sitePath('content/taxonomies'));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlNotHasKey('taxonomies', $this->collectionsPath('blog.yaml'));
    }

    /** @test */
    public function it_will_not_migrate_date_settings_if_none_are_referenced()
    {
        $path = $this->sitePath('content/collections/blog/folder.yaml');

        $this->files->put($path, str_replace('order:', 'not_order:', $this->files->get($path)));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlNotHasKey('date', $this->collectionsPath('blog.yaml'));
        $this->assertParsedYamlNotHasKey('date_behavior', $this->collectionsPath('blog.yaml'));
        $this->assertParsedYamlNotHasKey('sort_dir', $this->collectionsPath('blog.yaml'));
    }

    /** @test */
    public function it_migrates_number_ordered_collection()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'favs']);

        $expectedConfig = [
            'template' => 'blog/post',
            'structure' => [
                'max_depth' => 1,
                'tree' => [
                    ['entry' => '93c5ea5e-581d-4074-af70-1eeae01d7880'],
                    ['entry' => '82f60ba2-6c16-4889-8420-d1c8e7adfa3d'],
                ],
            ],
        ];

        $this->assertParsedYamlEquals($expectedConfig, $this->collectionsPath('favs.yaml'));
        $this->assertFileExists($this->collectionsPath('favs/red-shirt.md'));
        $this->assertFileExists($this->collectionsPath('favs/blue-shirt.md'));
        $this->assertFileExists($this->collectionsPath('favs/purple-shirt.md'));
    }

    /** @test */
    public function it_migrates_textile_and_html_extensions()
    {
        $this->files->move(
            $this->sitePath('content/collections/blog/2017-01-18.my-first-day.md'),
            $this->sitePath('content/collections/blog/2017-01-18.my-first-day.textile')
        );

        $this->files->move(
            $this->sitePath('content/collections/blog/_2017-01-19.paperwork-and-snowshoeing.md'),
            $this->sitePath('content/collections/blog/_2017-01-19.paperwork-and-snowshoeing.html')
        );

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileNotExists($this->collectionsPath('blog/2017-01-18.my-first-day.textile'));
        $this->assertFileNotExists($this->collectionsPath('blog/2017-01-19.paperwork-and-snowshoeing.html'));
        $this->assertFileExists($this->collectionsPath('blog/2017-01-18.my-first-day.md'));
        $this->assertFileExists($this->collectionsPath('blog/2017-01-19.paperwork-and-snowshoeing.md'));
    }

    /** @test */
    public function it_can_migrate_entry_content()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlContains(['intro_image' => 'img/stetson.jpg'], $this->collectionsPath('blog/2017-01-18.my-first-day.md'));
    }
}
