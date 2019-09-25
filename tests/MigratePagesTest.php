<?php

namespace Tests;

use Tests\TestCase;
use Tests\Console\Foundation\InteractsWithConsole;

class MigratePagesTest extends TestCase
{
    protected function paths()
    {
        return [
            base_path('content/collections'),
            base_path('content/structures'),
        ];
    }

    protected function collectionsPath($append = null)
    {
        return collect([base_path('content/collections'), $append])->filter()->implode('/');
    }

    /** @test */
    function it_migrates_correct_collection_and_structure_files()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/site/content/pages', $this->collectionsPath('pages'));

        $this->assertFileNotExists($this->collectionsPath('../structures/pages.yaml'));
        $this->assertFileNotExists($this->collectionsPath('pages.yaml'));
        $this->assertCount(1, $this->files->files($this->collectionsPath('pages')));
        $this->assertCount(5, $this->files->directories($this->collectionsPath('pages')));

        $this->artisan('statamic:migrate:pages');

        $this->assertFileExists($this->collectionsPath('../structures/pages.yaml'));
        $this->assertFileExists($this->collectionsPath('pages.yaml'));
        $this->assertCount(9, $this->files->files($this->collectionsPath('pages')));
        $this->assertCount(0, $this->files->directories($this->collectionsPath('pages')));
    }

    /** @test */
    function it_migrates_yaml_config()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/site/content/pages', $this->collectionsPath('pages'));

        $this->artisan('statamic:migrate:pages');

        $expected = [
            'title' => 'Pages',
            'route' => '{{ parent_uri }}/{{ slug }}',
            'blueprints' => [
                'home',
                'about',
                'gallery',
            ],
            'structure' => 'pages',
        ];

        $this->assertParsedYamlEquals($expected, $this->collectionsPath('pages.yaml'));
    }

    /** @test */
    function it_migrates_structure()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/site/content/pages', $this->collectionsPath('pages'));

        $this->artisan('statamic:migrate:pages');

        $expected = [
            'title' => 'Pages',
            'expects_root' => true,
            'root' => 'db0ae4e3-4f10-4802-bc40-0b880cbf02c7',
            'tree' => [
                [
                    'entry' => '72c016c6-cc0a-4928-b53b-3275f3f6da0a',
                    'children' => [
                        ['entry' => '7f48ceb3-97c5-45be-acd4-f88ff0284ed6']
                    ]
                ],
                ['entry' => '60962021-f154-4cd2-a1d7-035a12b6da9e'],
                [
                    'entry' => '3cd2d431-699c-417c-8d57-9183cd17a6fc',
                    'children' => [
                        [
                            'entry' => '1a45dfed-9d06-4493-83b1-dffe2522cbe7',
                            'children' => [
                                ['entry' => 'c50f5ee5-683d-4299-b16c-9271b7f9e41b']
                            ]
                        ]
                    ]
                ],
                ['entry' => '26a4ce21-d768-440d-806b-213918df0ee0'],
                ['entry' => 'de627bca-7595-429e-9b41-ad58703916d7']
            ]
        ];

        $this->assertParsedYamlEquals($expected, $this->collectionsPath('../structures/pages.yaml'));
    }
}
