<?php

namespace Tests;

class MigrateLocalizedPagesTest extends TestCase
{
    protected $siteFixture = 'site-localized';

    protected function path($append = null)
    {
        return collect([base_path('content/collections'), $append])->filter()->implode('/');
    }

    /** @test */
    public function it_migrates_expected_number_of_files()
    {
        $this->assertCount(2, $this->files->files($this->sitePath('content/pages')));
        $this->assertCount(2, $this->files->directories($this->sitePath('content/pages')));

        $this->artisan('statamic:migrate:pages');

        $this->assertFileExists($this->path('pages.yaml'));
        $this->assertCount(0, $this->files->files($this->path('pages')));
        $this->assertCount(2, $this->files->directories($this->path('pages')));
        $this->assertCount(5, $this->files->allFiles($this->path('pages')));
    }

    /** @test */
    public function it_migrates_yaml_config()
    {
        $this->artisan('statamic:migrate:pages');

        $expected = [
            'title' => 'Pages',
            'route' => '{{ parent_uri }}/{{ slug }}',
            'blueprints' => [],
            'structure' => [
                'root' => true,
                'tree' => [
                    ['entry' => 'db0ae4e3-4f10-4802-bc40-0b880cbf02c7'],
                    ['entry' => '72c016c6-cc0a-4928-b53b-3275f3f6da0a'],
                    ['entry' => '60962021-f154-4cd2-a1d7-035a12b6da9e'],
                ],
            ],
        ];

        $this->assertParsedYamlEquals($expected, $this->path('pages.yaml'));
    }

    /** @test */
    public function it_can_migrate_localized_page_content()
    {
        $this->artisan('statamic:migrate:pages');

        $this->assertParsedYamlContains(['avatar' => 'img/stetson.jpg'], $this->path('pages/default/about.md'));
        $this->assertParsedYamlContains(['avatar' => 'img/coffee-mug.jpg'], $this->path('pages/fr/les-aboot.md'));
    }
}
