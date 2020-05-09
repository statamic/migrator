<?php

namespace Tests;

use Statamic\Migrator\YAML;

class MigrateLocalizedCollectionTest extends TestCase
{
    protected $siteFixture = 'site-localized';

    protected function path($append = null)
    {
        return collect([base_path('content/collections'), $append])->filter()->implode('/');
    }

    private function migrateCollection($config)
    {
        $path = $this->sitePath('content/collections/test/folder.yaml');

        $this->prepareFolder($path);

        $this->files->put($path, YAML::dump($config));

        $this->artisan('statamic:migrate:collection', ['handle' => 'test']);

        return YAML::parse($this->files->get($this->path('test.yaml')));
    }

    /** @test */
    public function it_can_migrate_a_collection()
    {
        $this->assertFileNotExists($this->path('blog'));
        $this->assertFileNotExists($this->path('blog.yaml'));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileNotExists($this->path('blog/folder.yaml'));
        $this->assertFileExists($this->path('blog.yaml'));
        $this->assertCount(3, $this->files->allFiles($this->path('blog')));

        $this->assertFileNotExists($this->path('blog/2017-07-31.english-fire.md'));
        $this->assertFileExists($this->path('blog/default/2017-07-31.english-fire.md'));
        $this->assertFileExists($this->path('blog/fr/2017-07-31.le-fire.md'));

        $this->assertFileNotExists($this->path('blog/2017-03-08.spring-wonderful-spring.md'));
        $this->assertFileNotExists($this->path('blog/_2017-03-08.spring-wonderful-spring.md'));
        $this->assertFileExists($this->path('blog/default/2017-03-08.spring-wonderful-spring.md'));
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
            'blueprints' => [
                'content',
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

        $this->assertParsedYamlEquals($expected, $this->path('blog.yaml'));
    }
}
