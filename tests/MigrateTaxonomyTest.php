<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Path;
use Statamic\Migrator\YAML;

class MigrateTaxonomyTest extends TestCase
{
    protected function paths()
    {
        return [
            'taxonomies' => base_path('content/taxonomies'),
            'blueprints' => resource_path('blueprints/taxonomies'),
        ];
    }

    protected function taxonomiesPath($append = null)
    {
        return Path::tidy(collect([base_path('content/taxonomies'), $append])->filter()->implode('/'));
    }

    protected function blueprintsPath($append = null)
    {
        return Path::tidy(collect([resource_path('blueprints/taxonomies'), $append])->filter()->implode('/'));
    }

    #[Test]
    public function it_can_migrate_a_taxonomy()
    {
        $this->assertFileNotExists($this->taxonomiesPath('tags'));
        $this->assertFileNotExists($this->taxonomiesPath('tags.yaml'));
        $this->assertFileNotExists($this->blueprintsPath('tags'));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertFileExists($this->taxonomiesPath('tags.yaml'));
        $this->assertCount(2, $this->files->files($this->taxonomiesPath('tags')));
        $this->assertCount(2, $this->files->files($this->blueprintsPath('tags')));
        $this->assertParsedYamlContains(['order' => 1], $this->blueprintsPath('tags/default.yaml'));
    }

    #[Test]
    public function it_can_migrate_a_custom_default_blueprint()
    {
        $this->files->put($this->prepareFolder($this->blueprintsPath('tags/default.yaml')), YAML::dump([
            'title' => 'Default',
            'custom' => 'stuff',
        ]));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertCount(2, $this->files->files($this->blueprintsPath('tags')));
        $this->assertParsedYamlContains(['custom' => 'stuff', 'order' => 1], $this->blueprintsPath('tags/default.yaml'));
    }

    #[Test]
    public function it_migrates_yaml_config()
    {
        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $expected = [
            'title' => 'Tags',
            'route' => '/blog/tags/{slug}',
        ];

        $this->assertParsedYamlEquals($expected, $this->taxonomiesPath('tags.yaml'));
    }

    #[Test]
    public function it_migrates_without_a_route()
    {
        $this->files->delete($this->sitePath('settings/routes.yaml'));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertParsedYamlNotHasKey('route', $this->taxonomiesPath('tags.yaml'));
    }

    #[Test]
    public function it_migrates_without_a_terms_folder()
    {
        $this->files->deleteDirectory($this->sitePath('content/taxonomies/tags'));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertFileNotExists($this->taxonomiesPath('tags'));
    }

    #[Test]
    public function it_migrates_term_content_under_content_key()
    {
        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $expected = <<<'EOT'
title: spring
content: 'Spring has sprung!'
blueprint: tag

EOT;

        $this->assertEquals(
            static::normalizeMultilineString($expected),
            static::normalizeMultilineString($this->files->get($this->taxonomiesPath('tags/spring.yaml')))
        );
    }

    #[Test]
    public function it_migrates_into_empty_terms_folder_without_complaining()
    {
        $this->files->makeDirectory($this->taxonomiesPath('tags'));

        $this->assertFileExists($this->taxonomiesPath('tags'));
        $this->assertFileNotExists($this->taxonomiesPath('tags.yaml'));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertFileExists($this->taxonomiesPath('tags.yaml'));
        $this->assertCount(2, $this->files->files($this->taxonomiesPath('tags')));
    }

    #[Test]
    public function it_wont_migrate_into_a_populated_terms_folder()
    {
        $this->files->makeDirectory($this->taxonomiesPath('tags'));
        $this->files->put($this->taxonomiesPath('tags/llamas.yaml'), '');

        $this->assertFileExists($this->taxonomiesPath('tags/llamas.yaml'));
        $this->assertFileNotExists($this->taxonomiesPath('tags.yaml'));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertFileNotExists($this->taxonomiesPath('tags.yaml'));
        $this->assertCount(1, $this->files->files($this->taxonomiesPath('tags')));
    }
}
