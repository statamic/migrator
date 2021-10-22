<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\Migrator\YAML;

class MigratePagesTest extends TestCase
{
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
    public function it_migrates_pages_to_a_collection()
    {
        $this->assertCount(1, $this->files->files($this->sitePath('content/pages')));
        $this->assertCount(5, $this->files->directories($this->sitePath('content/pages')));
        $this->assertFileNotExists($this->blueprintsPath('pages'));

        $this->artisan('statamic:migrate:pages');

        $this->assertFileExists($this->collectionsPath('pages.yaml'));
        $this->assertCount(11, $this->files->files($this->collectionsPath('pages')));
        $this->assertCount(0, $this->files->directories($this->collectionsPath('pages')));
        $this->assertCount(5, $this->files->files($this->blueprintsPath('pages')));
        $this->assertParsedYamlContains(['order' => 1], $this->blueprintsPath('pages/default.yaml'));
    }

    /** @test */
    public function it_migrates_page_fieldset()
    {
        $this->assertFileNotExists($this->blueprintsPath('pages/gallery.yaml'));

        $this->artisan('statamic:migrate:pages');

        $path = $this->collectionsPath('pages/gallery.md');

        $this->assertFileExists($this->blueprintsPath('pages/gallery.yaml'));
        $this->assertParsedYamlContains(['blueprint' => 'gallery'], $path);
        $this->assertParsedYamlNotHasKey('fieldset', $path);
    }

    /** @test */
    public function it_can_migrate_a_custom_default_blueprint()
    {
        $this->files->put($this->prepareFolder($this->blueprintsPath('pages/default.yaml')), YAML::dump([
            'title' => 'Default',
            'custom' => 'stuff',
        ]));

        $this->artisan('statamic:migrate:pages');

        $this->assertCount(5, $this->files->files($this->blueprintsPath('pages')));
        $this->assertParsedYamlContains(['custom' => 'stuff', 'order' => 1], $this->blueprintsPath('pages/default.yaml'));
    }

    /** @test */
    public function it_migrates_yaml_config()
    {
        $this->artisan('statamic:migrate:pages');

        $expectedConfig = [
            'title' => 'Pages',
            'route' => '{{ parent_uri }}/{{ slug }}',
            'structure' => [
                'root' => true,
            ],
        ];

        $expectedTree = [
            'tree' => [
                ['entry' => 'db0ae4e3-4f10-4802-bc40-0b880cbf02c7'],
                [
                    'entry' => '72c016c6-cc0a-4928-b53b-3275f3f6da0a',
                    'children' => [
                        ['entry' => '7f48ceb3-97c5-45be-acd4-f88ff0284ed6'],
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
                        ['entry' => '4313cd2d-9c69-7c41-78d5-17a6fc9183cd'],
                    ],
                ],
                [
                    'entry' => '26a4ce21-d768-440d-806b-213918df0ee0',
                    'children' => [
                        ['entry' => 'ce226a41-8d76-40d4-b806-8df391e00e21'],
                    ],
                ],
                ['entry' => 'de627bca-7595-429e-9b41-ad58703916d7'],
            ],
        ];

        $this->assertParsedYamlEquals($expectedConfig, $this->collectionsPath('pages.yaml'));
        $this->assertParsedYamlEquals($expectedTree, $this->treesPath('pages.yaml'));
    }

    /** @test */
    public function it_migrates_structure_if_there_are_no_pages()
    {
        $this->files->cleanDirectory(base_path('site/content/pages'));

        $this->artisan('statamic:migrate:pages');

        $expectedConfig = [
            'structure' => [
                'root' => true,
            ],
        ];

        $expectedTree = [
            'tree' => [],
        ];

        $this->assertParsedYamlContains($expectedConfig, $this->collectionsPath('pages.yaml'));
        $this->assertParsedYamlEquals($expectedTree, $this->treesPath('pages.yaml'));
    }

    /** @test */
    public function it_migrates_structure_if_only_home_page_exists()
    {
        collect($this->files->directories($this->sitePath('content/pages')))->each(function ($directory) {
            $this->files->deleteDirectory($directory);
        });

        $this->artisan('statamic:migrate:pages');

        $expectedConfig = [
            'structure' => [
                'root' => true,
            ],
        ];

        $expectedTree = [
            'tree' => [
                ['entry' => 'db0ae4e3-4f10-4802-bc40-0b880cbf02c7'],
            ],
        ];

        $this->assertParsedYamlContains($expectedConfig, $this->collectionsPath('pages.yaml'));
        $this->assertParsedYamlEquals($expectedTree, $this->treesPath('pages.yaml'));
    }

    /** @test */
    public function it_migrates_root_page_slug_off_title_and_other_page_slugs_off_v2_folder_name()
    {
        $this->artisan('statamic:migrate:pages');

        $expected = [
            'about',
            'about-sub-page',
            'blog',
            'contact', // Generated from /contact
            'contact', // Generated from /gallery/contact
            'contact', // Generated from /things/contact
            'gallery',
            'gallery-sub-page',
            'gallery-sub-sub-page',
            'home', // Generated from home page `title`
            'things',
        ];

        $this->assertEquals($expected, Entry::all()->map->slug()->sort()->values()->all());
    }

    /** @test */
    public function it_migrates_duplicate_slugs_at_different_uri_levels_by_incrementing_filenames()
    {
        $this->artisan('statamic:migrate:pages');

        $this->assertEquals(
            $this->collectionsPath('pages/contact.md'),
            Entry::findByUri('/gallery/contact')->path()
        );

        $this->assertEquals(
            $this->collectionsPath('pages/contact.2.md'),
            Entry::findByUri('/things/contact')->path()
        );

        $this->assertEquals(
            $this->collectionsPath('pages/contact.3.md'),
            Entry::findByUri('/contact')->path()
        );
    }

    /** @test */
    public function it_migrates_pages_without_order()
    {
        $this->files->moveDirectory($this->sitePath('content/pages/5.contact'), $this->sitePath('content/pages/contact'));

        $this->artisan('statamic:migrate:pages');

        $this->assertContains('contact', Entry::all()->map->slug()->all());
    }

    /** @test */
    public function it_migrates_textile_and_html_extensions()
    {
        $this->files->move(
            $this->sitePath('content/pages/2.blog/index.md'),
            $this->sitePath('content/pages/2.blog/index.textile')
        );

        $this->files->move(
            $this->sitePath('content/pages/3.gallery/index.md'),
            $this->sitePath('content/pages/3.gallery/index.html')
        );

        $this->artisan('statamic:migrate:pages');

        $this->assertFileNotExists($this->collectionsPath('pages/blog.textile'));
        $this->assertFileNotExists($this->collectionsPath('pages/gallery.html'));
        $this->assertFileExists($this->collectionsPath('pages/blog.md'));
        $this->assertFileExists($this->collectionsPath('pages/gallery.md'));
    }

    /** @test */
    public function it_can_migrate_page_content()
    {
        $this->artisan('statamic:migrate:pages');

        $this->assertParsedYamlContains(['avatar' => 'img/stetson.jpg'], $this->collectionsPath('pages/about.md'));
    }
}
