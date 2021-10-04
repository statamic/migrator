<?php

namespace Tests;

use Facades\Statamic\Migrator\UUID;
use Statamic\Migrator\YAML;
use Tests\Fakes\FakeUUID;

class MigrateLocalizedPagesTest extends TestCase
{
    protected $siteFixture = 'site-localized';

    protected function paths()
    {
        return [
            'collections' => base_path('content/collections'),
            'trees' => base_path('content/trees/collections'),
            'blueprints' => resource_path('blueprints/collections'),
        ];
    }

    protected function collectionsPath($append = null)
    {
        return collect([base_path('content/collections'), $append])->filter()->implode('/');
    }

    protected function treesPath($append = null)
    {
        return collect([base_path('content/trees/collections'), $append])->filter()->implode('/');
    }

    protected function blueprintsPath($append = null)
    {
        return collect([resource_path('blueprints/collections'), $append])->filter()->implode('/');
    }

    /** @test */
    public function it_migrates_expected_number_of_files()
    {
        $this->assertCount(2, $this->files->files($this->sitePath('content/pages')));
        $this->assertCount(10, $this->files->directories($this->sitePath('content/pages')));

        $this->artisan('statamic:migrate:pages');

        $this->assertFileExists($this->collectionsPath('pages.yaml'));

        $this->assertCount(0, $this->files->files($this->collectionsPath('pages')));
        $this->assertCount(32, $this->files->allFiles($this->collectionsPath('pages')));
        $this->assertCount(2, $this->files->directories($this->collectionsPath('pages')));
        $this->assertCount(16, $this->files->files($this->collectionsPath('pages/default')));
        $this->assertCount(16, $this->files->files($this->collectionsPath('pages/fr')));
    }

    /** @test */
    public function it_migrates_yaml_config()
    {
        UUID::swap(new FakeUUID);

        $this->artisan('statamic:migrate:pages');

        $expectedConfig = [
            'title' => 'Pages',
            'route' => '{{ parent_uri }}/{{ slug }}',
            'sites' => [
                'default',
                'fr',
            ],
            'structure' => [
                'root' => true,
            ],
        ];

        $expectedDefaultTree = [
            'tree' => [
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
                ['entry' => '72c016c6-cc0a-4928-b53b-3275f3f6da0a'],
                ['entry' => '3cd2d431-699c-417c-8d57-9183cd17a6fc'],
                ['entry' => '56b5f7a0-adcd-4490-bcaa-dad3b8feef6d'],
                ['entry' => '2efee6c0-c3a5-44dc-a3db-a0af7fa73977'],
                ['entry' => 'e2126a4c-8d76-d440-b806-df0ee0213918'],
            ],
        ];

        $expectedFrenchTree = [
            'tree' => [
                ['entry' => 'fr-db0ae4e3-4f10-4802-bc40-0b880cbf02c7'],
                [
                    'entry' => 'fr-72c016c6-cc0a-4928-b53b-3275f3f6da0a',
                    'children' => [
                        ['entry' => 'fr-7f48ceb3-97c5-45be-acd4-f88ff0284ed6'],
                        ['entry' => 'fr-f748ceb3-97c5-45be-acd4-f88ff0249e71'],
                        ['entry' => 'fr-4ef748b3-97c5-acd4-be45-f8849e71ff02'],
                    ],
                ],
                ['entry' => 'fr-60962021-f154-4cd2-a1d7-035a12b6da9e'],
                [
                    'entry' => 'fr-3cd2d431-699c-417c-8d57-9183cd17a6fc',
                    'children' => [
                        [
                            'entry' => 'fr-1a45dfed-9d06-4493-83b1-dffe2522cbe7',
                            'children' => [
                                ['entry' => 'fr-c50f5ee5-683d-4299-b16c-9271b7f9e41b'],
                            ],
                        ],
                    ],
                ],
                ['entry' => 'fr-26a4ce21-d768-440d-806b-213918df0ee0'],
                ['entry' => 'fr-de627bca-7595-429e-9b41-ad58703916d7'],
                ['entry' => 'fr-72c016c6-cc0a-4928-b53b-3275f3f6da0a'],
                ['entry' => 'fr-3cd2d431-699c-417c-8d57-9183cd17a6fc'],
                ['entry' => 'fr-56b5f7a0-adcd-4490-bcaa-dad3b8feef6d'],
                ['entry' => 'fr-2efee6c0-c3a5-44dc-a3db-a0af7fa73977'],
                ['entry' => 'fr-e2126a4c-8d76-d440-b806-df0ee0213918'],
            ],
        ];

        $this->assertParsedYamlEquals($expectedConfig, $this->collectionsPath('pages.yaml'));
        $this->assertParsedYamlEquals($expectedDefaultTree, $this->treesPath('default/pages.yaml'));
        $this->assertParsedYamlEquals($expectedFrenchTree, $this->treesPath('fr/pages.yaml'));
    }

    /** @test */
    public function it_can_migrate_an_explicitly_localized_page()
    {
        $this->artisan('statamic:migrate:pages');

        $this->assertFileNotExists($this->collectionsPath('pages/gallery.md'));
        $this->assertFileExists($defaultPath = $this->collectionsPath('pages/default/gallery.md'));
        $this->assertFileExists($frenchPath = $this->collectionsPath('pages/fr/les-gallery.md'));

        $defaultEntry = YAML::parse($this->files->get($defaultPath));
        $frenchEntry = YAML::parse($this->files->get($frenchPath));

        $this->assertNotEquals($defaultEntry['id'], $frenchEntry['id']);
        $this->assertEquals($defaultEntry['id'], $frenchEntry['origin']);
        $this->assertNotNull($frenchEntry['id']);
        $this->assertArrayNotHasKey('fieldset', $defaultEntry);
        $this->assertArrayNotHasKey('fieldset', $frenchEntry);
    }

    /** @test */
    public function it_can_migrate_an_implicitly_localized_page()
    {
        $this->artisan('statamic:migrate:pages');

        $this->assertFileNotExists($this->collectionsPath('pages/blog.md'));
        $this->assertFileExists($defaultPath = $this->collectionsPath('pages/default/blog.md'));
        $this->assertFileExists($frenchPath = $this->collectionsPath('pages/fr/blog.md'));

        $defaultEntry = YAML::parse($this->files->get($defaultPath));
        $frenchEntry = YAML::parse($this->files->get($frenchPath));

        $expectedFrenchEntry = [
            'id' => $frenchEntry['id'],
            'origin' => '60962021-f154-4cd2-a1d7-035a12b6da9e',
            'slug' => 'blog',
            'published' => true,
        ];

        $this->assertEquals($expectedFrenchEntry, $frenchEntry);
        $this->assertNotEquals($defaultEntry['id'], $frenchEntry['id']);
        $this->assertEquals($defaultEntry['id'], $frenchEntry['origin']);
    }

    /** @test */
    public function it_can_migrate_a_localized_page_fieldset()
    {
        $this->assertFileNotExists($this->blueprintsPath('pages/gallery.yaml'));

        $this->artisan('statamic:migrate:pages');

        $this->assertFileExists($this->blueprintsPath('pages/gallery.yaml'));

        $defaultPath = $this->collectionsPath('pages/default/gallery.md');
        $frenchPath = $this->collectionsPath('pages/fr/les-gallery.md');

        $this->assertParsedYamlContains(['blueprint' => 'gallery'], $defaultPath);
        $this->assertParsedYamlContains(['blueprint' => 'gallery'], $frenchPath);
        $this->assertParsedYamlNotHasKey('fieldset', $defaultPath);
        $this->assertParsedYamlNotHasKey('fieldset', $frenchPath);
    }

    /** @test */
    public function it_can_migrate_localized_page_content()
    {
        $this->artisan('statamic:migrate:pages');

        $this->assertParsedYamlContains(['avatar' => 'img/stetson.jpg'], $this->collectionsPath('pages/default/about.md'));
        $this->assertParsedYamlContains(['avatar' => 'img/coffee-mug.jpg'], $this->collectionsPath('pages/fr/les-aboot.md'));
    }

    /** @test */
    public function it_can_migrate_localized_published_statuses()
    {
        $this->artisan('statamic:migrate:pages');

        $this->assertParsedYamlContains(['published' => true], $this->collectionsPath('pages/default/only-english.md'));
        $this->assertParsedYamlContains(['published' => false], $this->collectionsPath('pages/fr/only-english.md'));

        $this->assertParsedYamlContains(['published' => false], $this->collectionsPath('pages/default/only-french.md'));
        $this->assertParsedYamlContains(['published' => true], $this->collectionsPath('pages/fr/only-french.md'));

        $this->assertParsedYamlContains(['published' => true], $this->collectionsPath('pages/default/published-in-both.md'));
        $this->assertParsedYamlContains(['published' => true], $this->collectionsPath('pages/fr/published-in-both.md'));

        $this->assertParsedYamlContains(['published' => false], $this->collectionsPath('pages/default/published-in-neither.md'));
        $this->assertParsedYamlContains(['published' => false], $this->collectionsPath('pages/fr/published-in-neither.md'));
    }
}
