<?php

namespace Tests;

class MigrateTaxonomyTest extends TestCase
{
    protected function path($append = null)
    {
        return collect([base_path('content/taxonomies'), $append])->filter()->implode('/');
    }

    /** @test */
    public function it_can_migrate_a_taxonomy()
    {
        $this->assertFileNotExists($this->path('tags'));
        $this->assertFileNotExists($this->path('tags.yaml'));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertFileExists($this->path('tags.yaml'));
        $this->assertCount(2, $this->files->files($this->path('tags')));
    }

    /** @test */
    public function it_migrates_yaml_config()
    {
        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $expected = [
            'title' => 'Tags',
            'blueprints' => [
                'tag',
            ],
            'route' => '/blog/tags/{slug}',
        ];

        $this->assertParsedYamlEquals($expected, $this->path('tags.yaml'));
    }

    /** @test */
    public function it_migrates_without_a_route()
    {
        $this->files->delete($this->sitePath('settings/routes.yaml'));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertParsedYamlNotHasKey('route', $this->path('tags.yaml'));
    }

    /** @test */
    public function it_migrates_without_a_terms_folder()
    {
        $this->files->deleteDirectory($this->sitePath('content/taxonomies/tags'));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertFileNotExists($this->path('tags'));
    }

    /** @test */
    public function it_migrates_term_content_under_content_key()
    {
        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $expected = <<<'EOT'
title: spring
content: 'Spring has sprung!'
blueprint: tag

EOT;

        $this->assertEquals($expected, $this->files->get($this->path('tags/spring.yaml')));
    }

    /** @test */
    public function it_migrates_into_empty_terms_folder_without_complaining()
    {
        $this->files->makeDirectory($this->path('tags'));

        $this->assertFileExists($this->path('tags'));
        $this->assertFileNotExists($this->path('tags.yaml'));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertFileExists($this->path('tags.yaml'));
        $this->assertCount(2, $this->files->files($this->path('tags')));
    }

    /** @test */
    public function it_wont_migrate_into_a_populated_terms_folder()
    {
        $this->files->makeDirectory($this->path('tags'));
        $this->files->put($this->path('tags/llamas.yaml'), '');

        $this->assertFileExists($this->path('tags/llamas.yaml'));
        $this->assertFileNotExists($this->path('tags.yaml'));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertFileNotExists($this->path('tags.yaml'));
        $this->assertCount(1, $this->files->files($this->path('tags')));
    }
}
