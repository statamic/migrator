<?php

namespace Tests;

class MigrateLocalizedTaxonomyTest extends TestCase
{
    protected $siteFixture = 'site-localized';

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
        $this->assertCount(4, $this->files->files($this->path('tags')));
    }

    /** @test */
    public function it_migrates_yaml_config()
    {
        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $expected = [
            'sites' => [
                'default',
                'fr',
            ],
            'title' => 'Tags',
            'route' => '/blog/tags/{slug}',
            'template' => 'blog/taxonomy',
        ];

        $this->assertParsedYamlEquals($expected, $this->path('tags.yaml'));
    }

    /** @test */
    public function it_can_migrate_a_localized_term()
    {
        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $expected = [
            'title' => 'coffee',
            'content' => 'wat',
            'thumbnail' => 'img/coffee-mug.jpg',
            'blueprint' => 'tag',
            'localizations' => [
                'fr' => [
                    'title' => 'le coffvefe',
                    'content' => 'Le Neato',
                    'slug' => 'le-coffvefe-les-slug',
                ],
            ],
        ];

        $this->assertParsedYamlEquals($expected, $this->path('tags/coffee.yaml'));
    }

    /** @test */
    public function it_can_migrate_a_localized_term_with_partially_filled_content()
    {
        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $expected = [
            'title' => 'harry-potter',
            'blueprint' => 'tag',
            'localizations' => [
                'fr' => [
                    'title' => 'le-harry-potter',
                ],
            ],
        ];

        $this->assertParsedYamlEquals($expected, $this->path('tags/harry-potter.yaml'));
    }

    /** @test */
    public function it_can_migrate_a_term_without_localized_content()
    {
        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $expected = [
            'title' => 'spring',
            'blueprint' => 'tag',
        ];

        $this->assertParsedYamlEquals($expected, $this->path('tags/spring.yaml'));
    }

    /** @test */
    public function it_can_migrate_a_localized_term_without_default_site_content()
    {
        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $expected = [
            'title' => 'smells',
            'blueprint' => 'tag',
            'localizations' => [
                'fr' => [
                    'title' => 'la smells',
                ],
            ],
        ];

        $this->assertParsedYamlEquals($expected, $this->path('tags/smells.yaml'));
    }
}
