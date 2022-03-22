<?php

namespace Tests;

use Statamic\Facades\Path;
use Statamic\Migrator\YAML;

class MigrateLocalizedCollectionTest extends TestCase
{
    protected $siteFixture = 'site-localized';

    protected function paths()
    {
        return [
            'collections' => base_path('content/collections'),
            'blueprints' => resource_path('blueprints/collections'),
        ];
    }

    protected function collectionsPath($append = null)
    {
        return Path::tidy(collect([base_path('content/collections'), $append])->filter()->implode('/'));
    }

    protected function blueprintsPath($append = null)
    {
        return Path::tidy(collect([resource_path('blueprints/collections'), $append])->filter()->implode('/'));
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

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileNotExists($this->collectionsPath('blog/folder.yaml'));
        $this->assertFileExists($this->collectionsPath('blog.yaml'));
        $this->assertCount(3, $this->files->allFiles($this->collectionsPath('blog')));
    }

    /** @test */
    public function it_migrates_yaml_config()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $expected = [
            'sites' => [
                'default',
                'fr',
            ],
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
    public function it_can_migrate_a_draft_entry_that_was_not_localized()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileNotExists($this->collectionsPath('blog/2017-03-08.spring-wonderful-spring.md'));
        $this->assertFileNotExists($this->collectionsPath('blog/_2017-03-08.spring-wonderful-spring.md'));
        $this->assertFileExists($this->collectionsPath('blog/default/2017-03-08.spring-wonderful-spring.md'));
    }

    /** @test */
    public function it_can_migrate_a_localized_entry()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileNotExists($this->collectionsPath('blog/2017-07-31.english-fire.md'));
        $this->assertFileExists($defaultPath = $this->collectionsPath('blog/default/2017-07-31.english-fire.md'));
        $this->assertFileExists($frenchPath = $this->collectionsPath('blog/fr/2017-07-31.le-fire.md'));

        $defaultEntry = YAML::parse($this->files->get($defaultPath));
        $frenchEntry = YAML::parse($this->files->get($frenchPath));

        $this->assertNotEquals($defaultEntry['id'], $frenchEntry['id']);
        $this->assertEquals($defaultEntry['id'], $frenchEntry['origin']);
        $this->assertNotNull($frenchEntry['id']);
    }

    /** @test */
    public function it_can_migrate_a_localized_entry_fieldset()
    {
        $this->assertFileNotExists($this->blueprintsPath('blog/content.yaml'));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileExists($this->blueprintsPath('blog/content.yaml'));

        $defaultPath = $this->collectionsPath('blog/default/2017-07-31.english-fire.md');
        $frenchPath = $this->collectionsPath('blog/fr/2017-07-31.le-fire.md');

        $this->assertParsedYamlContains(['blueprint' => 'content'], $defaultPath);
        $this->assertParsedYamlContains(['blueprint' => 'content'], $frenchPath);
        $this->assertParsedYamlNotHasKey('fieldset', $defaultPath);
        $this->assertParsedYamlNotHasKey('fieldset', $frenchPath);
    }

    /** @test */
    public function it_can_migrate_localized_entry_content()
    {
        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertParsedYamlContains(
            ['image' => 'img/redwood-james-irvine-trail.jpg'],
            $this->collectionsPath('blog/default/2017-07-31.english-fire.md')
        );

        $this->assertParsedYamlContains(
            ['image' => 'img/coffee-mug.jpg'],
            $this->collectionsPath('blog/fr/2017-07-31.le-fire.md')
        );
    }
}
