<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Path;

class MigrateLocalizedTaxonomyTest extends TestCase
{
    protected $siteFixture = 'site-localized';

    protected function path($append = null)
    {
        return Path::tidy(collect([base_path('content/taxonomies'), $append])->filter()->implode('/'));
    }

    #[Test]
    public function it_can_migrate_a_taxonomy()
    {
        $this->assertFileNotExists($this->path('tags'));
        $this->assertFileNotExists($this->path('tags.yaml'));

        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $this->assertFileExists($this->path('tags.yaml'));
        $this->assertCount(4, $this->files->files($this->path('tags')));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_can_migrate_a_term_without_localized_content()
    {
        $this->artisan('statamic:migrate:taxonomy', ['handle' => 'tags']);

        $expected = [
            'title' => 'spring',
            'blueprint' => 'tag',
        ];

        $this->assertParsedYamlEquals($expected, $this->path('tags/spring.yaml'));
    }

    #[Test]
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
