<?php

namespace Tests;

use Facades\Statamic\Migrator\UUID;
use Tests\Fakes\FakeUUID;

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
        $this->assertCount(5, $this->files->directories($this->sitePath('content/pages')));

        $this->artisan('statamic:migrate:pages');

        $this->assertFileExists($this->path('pages.yaml'));

        $this->assertCount(0, $this->files->files($this->path('pages')));
        $this->assertCount(17, $this->files->allFiles($this->path('pages')));
        $this->assertCount(2, $this->files->directories($this->path('pages')));
        $this->assertCount(11, $this->files->files($this->path('pages/default')));
        $this->assertCount(6, $this->files->files($this->path('pages/fr')));
    }

    /** @test */
    public function it_migrates_yaml_config()
    {
        UUID::swap(new FakeUUID);

        $this->artisan('statamic:migrate:pages');

        $expected = [
            'title' => 'Pages',
            'route' => '{{ parent_uri }}/{{ slug }}',
            'sites' => [
                'default',
                'fr',
            ],
            'structure' => [
                'root' => true,
                'tree' => [
                    'default' => [
                        ['entry' => 'db0ae4e3-4f10-4802-bc40-0b880cbf02c7'],
                        [
                            'entry' => '72c016c6-cc0a-4928-b53b-3275f3f6da0a',
                            'children' => [
                                ['entry' => '7f48ceb3-97c5-45be-acd4-f88ff0284ed6'],
                                ['entry' => 'f748ceb3-97c5-45be-acd4-f88ff0249e71'],
                                ['entry' => '4ef748b3-97c5-acd4-be45-f8849e71ff02'],
                            ],
                        ],
                        ['entry' => '60962021-f154-4cd2-a1d7-035a12b6da9e'],
                        [
                            'entry' => '3cd2d431-699c-417c-8d57-9183cd17a6fc',
                            'children' => [
                                [
                                    'entry' => '1a45dfed-9d06-4493-83b1-dffe2522cbe7',
                                    'children' => [
                                        ['entry' => 'c50f5ee5-683d-4299-b16c-9271b7f9e41b'],
                                    ],
                                ],
                            ],
                        ],
                        ['entry' => '26a4ce21-d768-440d-806b-213918df0ee0'],
                        ['entry' => 'de627bca-7595-429e-9b41-ad58703916d7'],
                    ],
                    'fr' => [
                        ['entry' => 'fr-db0ae4e3-4f10-4802-bc40-0b880cbf02c7'],
                        [
                            'entry' => 'fr-72c016c6-cc0a-4928-b53b-3275f3f6da0a',
                            'children' => [
                                ['entry' => 'fr-7f48ceb3-97c5-45be-acd4-f88ff0284ed6'],
                                ['entry' => 'fr-4ef748b3-97c5-acd4-be45-f8849e71ff02'],
                            ],
                        ],
                        [
                            'entry' => 'fr-3cd2d431-699c-417c-8d57-9183cd17a6fc',
                            'children' => [
                                ['entry' => 'fr-1a45dfed-9d06-4493-83b1-dffe2522cbe7'],
                            ],
                        ],
                    ],
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
