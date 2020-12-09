<?php

namespace Tests;

use Statamic\Migrator\ContentMigrator;
use Statamic\Migrator\YAML;

class ContentMigratorTest extends TestCase
{
    protected function paths($key = null)
    {
        $paths = [
            'speaker' => $this->sitePath('settings/fieldsets/speaker.yaml'),
            'fieldsets' => $this->sitePath('settings/fieldsets'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    private function addFieldset($handle, $fieldsetConfig)
    {
        $this->files->put($this->paths('fieldsets')."/{$handle}.yaml", YAML::dump($fieldsetConfig));

        return $this;
    }

    private function setFields($fields, $rawFieldset = false)
    {
        $this->assertFileNotExists($this->paths('speaker'));

        $fieldset = $rawFieldset ? $fields : [
            'sections' => [
                'main' => [
                    'fields' => $fields,
                ],
            ],
        ];

        $this->files->put($this->paths('speaker'), YAML::dump($fieldset));

        $this->assertFileExists($this->paths('speaker'));

        return $this;
    }

    private function migrateContent($content)
    {
        return ContentMigrator::usingFieldset('speaker')->migrateContent($content);
    }

    /** @test */
    public function it_can_migrate_assets_fields()
    {
        $content = $this
            ->setFields([
                'hero' => [
                    'type' => 'assets',
                    'container' => 'main',
                    'max_files' => 1,
                ],
                'images' => [
                    'type' => 'assets',
                    'container' => 'main',
                ],
            ])
            ->migrateContent([
                'hero' => '/assets/img/coffee-mug.jpg',
                'images' => [
                    '/assets/img/coffee-mug.jpg',
                    '/assets/img/stetson.jpg',
                ],
            ]);

        $expected = [
            'hero' => 'img/coffee-mug.jpg',
            'images' => [
                'img/coffee-mug.jpg',
                'img/stetson.jpg',
            ],
            'blueprint' => 'speaker',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_can_migrate_assets_fields_without_container_url()
    {
        $this->files->put($this->sitePath('content/assets/main.yaml'), 'title: Main');

        $content = $this
            ->setFields([
                'hero' => [
                    'type' => 'assets',
                    'container' => 'main',
                    'max_files' => 1,
                ],
                'images' => [
                    'type' => 'assets',
                    'container' => 'main',
                ],
            ])
            ->migrateContent([
                'hero' => '/assets/img/coffee-mug.jpg',
                'images' => [
                    '/assets/img/coffee-mug.jpg',
                    '/assets/img/stetson.jpg',
                ],
            ]);

        $expected = [
            'hero' => 'assets/img/coffee-mug.jpg',
            'images' => [
                'assets/img/coffee-mug.jpg',
                'assets/img/stetson.jpg',
            ],
            'blueprint' => 'speaker',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_can_migrate_term_fields()
    {
        $content = $this
            ->setFields([
                'one_taxonomy' => [
                    'type' => 'taxonomy',
                    'taxonomy' => 'tags',
                ],
                'one_taxonomy_single_value' => [
                    'type' => 'taxonomy',
                    'taxonomy' => 'tags',
                    'max_items' => 1,
                ],
                'suggest_one_taxonomy' => [
                    'type' => 'suggest',
                    'mode' => 'taxonomy',
                    'taxonomy' => 'tags',
                ],
                'suggest_one_taxonomy_single_value' => [
                    'type' => 'suggest',
                    'mode' => 'taxonomy',
                    'taxonomy' => 'tags',
                    'max_items' => 1,
                ],
                'multiple_taxonomies' => [
                    'type' => 'taxonomy',
                    'taxonomy' => ['tags', 'colours'],
                ],
                'multiple_taxonomies_single_value' => [
                    'type' => 'taxonomy',
                    'taxonomy' => ['tags', 'colours'],
                    'max_items' => 1,
                ],
                'suggest_multiple_taxonomies' => [
                    'type' => 'suggest',
                    'mode' => 'taxonomy',
                    'taxonomy' => ['tags', 'colours'],
                ],
                'suggest_multiple_taxonomies_single_value' => [
                    'type' => 'suggest',
                    'mode' => 'taxonomy',
                    'taxonomy' => ['tags', 'colours'],
                    'max_items' => 1,
                ],
                'tags' => [  // When field handle matches taxonomy handle, don't do anything with prefixes.
                    'type' => 'taxonomy',
                    'taxonomy' => 'tags',
                ],
            ])
            ->migrateContent([
                'one_taxonomy' => ['coffee', 'frostburg-university', 'tags/if-field-handle-does-not-match-taxonomy-handle-remove-prefix'],
                'one_taxonomy_single_value' => 'coffee',
                'suggest_one_taxonomy' => ['coffee', 'frostburg-university'],
                'suggest_one_taxonomy_single_value' => 'coffee',
                'multiple_taxonomies' => ['tags/coffee', 'tags/frostburg-university', 'colours/red'],
                'multiple_taxonomies_single_value' => 'tags/coffee',
                'suggest_multiple_taxonomies' => ['tags/coffee', 'tags/frostburg-university', 'colours/red'],
                'suggest_multiple_taxonomies_single_value' => 'tags/coffee',
                'tags' => ['one-with-slug', 'Anotha As Title'], // When field handle matches taxonomy handle, don't do anything with prefixes.
            ]);

        $expected = [
            'one_taxonomy' => ['coffee', 'frostburg-university', 'if-field-handle-does-not-match-taxonomy-handle-remove-prefix'],
            'one_taxonomy_single_value' => 'coffee',
            'suggest_one_taxonomy' => ['coffee', 'frostburg-university'],
            'suggest_one_taxonomy_single_value' => 'coffee',
            'multiple_taxonomies' => ['tags::coffee', 'tags::frostburg-university', 'colours::red'],
            'multiple_taxonomies_single_value' => 'tags::coffee',
            'suggest_multiple_taxonomies' => ['tags::coffee', 'tags::frostburg-university', 'colours::red'],
            'suggest_multiple_taxonomies_single_value' => 'tags::coffee',
            'tags' => ['one-with-slug', 'Anotha As Title'],
            'blueprint' => 'speaker',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_can_migrate_when_fieldset_has_fields_in_sections()
    {
        $content = $this
            ->setFields([
                'sections' => [
                    'one' => [
                        'fields' => [
                            'image_in_section_one' => [
                                'type' => 'assets',
                                'container' => 'main',
                                'max_files' => 1,
                            ],
                        ],
                    ],
                    'two' => [
                        'fields' => [
                            'image_in_section_two' => [
                                'type' => 'assets',
                                'container' => 'main',
                                'max_files' => 1,
                            ],
                        ],
                    ],
                ],

            ], true)
            ->migrateContent([
                'image_in_section_one' => '/assets/img/coffee-mug.jpg',
                'image_in_section_two' => '/assets/img/stetson.jpg',
            ]);

        $expected = [
            'image_in_section_one' => 'img/coffee-mug.jpg',
            'image_in_section_two' => 'img/stetson.jpg',
            'blueprint' => 'speaker',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_can_migrate_when_fieldset_has_fields_at_top_level()
    {
        $content = $this
            ->setFields([
                'fields' => [
                    'image_at_top_level' => [
                        'type' => 'assets',
                        'container' => 'main',
                        'max_files' => 1,
                    ],
                ],
            ], true)
            ->migrateContent([
                'image_at_top_level' => '/assets/img/coffee-mug.jpg',
            ]);

        $expected = [
            'image_at_top_level' => 'img/coffee-mug.jpg',
            'blueprint' => 'speaker',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_can_migrate_fields_within_replicators()
    {
        $content = $this
            ->setFields([
                'some_replicator' => [
                    'type' => 'replicator',
                    'sets' => [
                        'set_1' => [
                            'fields' => [
                                'image_within_replicator_set_1' => [
                                    'type' => 'assets',
                                    'container' => 'main',
                                    'max_files' => 1,
                                ],
                            ],
                        ],
                        'set_2' => [
                            'fields' => [
                                'nested_replicator' => [
                                    'type' => 'replicator',
                                    'sets' => [
                                        'nested_set' => [
                                            'fields' => [
                                                'image_within_replicator_set_2' => [
                                                    'type' => 'assets',
                                                    'container' => 'main',
                                                    'max_files' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->migrateContent([
                'some_replicator' => [
                    [
                        'type' => 'set_1',
                        'image_within_replicator_set_1' => '/assets/img/coffee-mug.jpg',
                    ],
                    [
                        'type' => 'set_2',
                        'nested_replicator' => [
                            [
                                'type' => 'nested_set',
                                'image_within_replicator_set_2' => '/assets/img/stetson.jpg',
                            ],
                        ],
                    ],
                ],
            ]);

        $expected = [
            'some_replicator' => [
                [
                    'type' => 'set_1',
                    'image_within_replicator_set_1' => 'img/coffee-mug.jpg',
                ],
                [
                    'type' => 'set_2',
                    'nested_replicator' => [
                        [
                            'type' => 'nested_set',
                            'image_within_replicator_set_2' => 'img/stetson.jpg',
                        ],
                    ],
                ],
            ],
            'blueprint' => 'speaker',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_can_migrate_fields_within_bards()
    {
        $content = $this
            ->setFields([
                'some_bard' => [
                    'type' => 'bard',
                    'buttons' => [
                        'bold',
                        'italic',
                    ],
                    'sets' => [
                        'set_1' => [
                            'fields' => [
                                'image_within_bard_set_1' => [
                                    'type' => 'assets',
                                    'container' => 'main',
                                    'max_files' => 1,
                                ],
                            ],
                        ],
                        'set_2' => [
                            'fields' => [
                                'nested_bard' => [
                                    'type' => 'bard',
                                    'sets' => [
                                        'nested_set' => [
                                            'fields' => [
                                                'image_within_bard_set_2' => [
                                                    'type' => 'assets',
                                                    'container' => 'main',
                                                    'max_files' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'nested_bard_with_no_sets' => [
                                    'type' => 'bard',
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->migrateContent([
                'some_bard' => [
                    [
                        'type' => 'set_1',
                        'image_within_bard_set_1' => '/assets/img/coffee-mug.jpg',
                    ],
                    [
                        'type' => 'set_2',
                        'nested_bard' => [
                            [
                                'type' => 'nested_set',
                                'image_within_bard_set_2' => '/assets/img/stetson.jpg',
                            ],
                        ],
                    ],
                ],
            ]);

        $expected = [
            'some_bard' => [
                [
                    'type' => 'set_1',
                    'image_within_bard_set_1' => 'img/coffee-mug.jpg',
                ],
                [
                    'type' => 'set_2',
                    'nested_bard' => [
                        [
                            'type' => 'nested_set',
                            'image_within_bard_set_2' => 'img/stetson.jpg',
                        ],
                    ],
                ],
            ],
            'blueprint' => 'speaker',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_can_migrate_fields_within_grids()
    {
        $content = $this
            ->setFields([
                'some_grid' => [
                    'type' => 'grid',
                    'fields' => [
                        'image_within_grid' => [
                            'type' => 'assets',
                            'container' => 'main',
                            'max_files' => 1,
                        ],
                        'nested_grid' => [
                            'type' => 'grid',
                            'fields' => [
                                'image_within_nested_grid' => [
                                    'type' => 'assets',
                                    'container' => 'main',
                                    'max_files' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->migrateContent([
                'some_grid' => [
                    [
                        'image_within_grid' => '/assets/img/coffee-mug.jpg',
                    ],
                    [
                        'nested_grid' => [
                            [
                                'image_within_nested_grid' => '/assets/img/stetson.jpg',
                            ],
                        ],
                    ],
                ],
            ]);

        $expected = [
            'some_grid' => [
                [
                    'image_within_grid' => 'img/coffee-mug.jpg',
                ],
                [
                    'nested_grid' => [
                        [
                            'image_within_nested_grid' => 'img/stetson.jpg',
                        ],
                    ],
                ],
            ],
            'blueprint' => 'speaker',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_can_migrate_fields_within_partials()
    {
        $content = $this
            ->addFieldset('country', [
                'title' => 'Country',
                'fields' => [
                    'country' => [
                        'type' => 'text',
                    ],
                    'flag' => [
                        'type' => 'assets',
                        'container' => 'main',
                        'max_files' => 1,
                    ],
                ],
            ])
            ->addFieldset('allegiances', [
                'title' => 'Allegiances',
                'fields' => [
                    'allegiances' => [
                        'type' => 'grid',
                        'fields' => [
                            'country' => [
                                'type' => 'partial',
                                'fieldset' => 'country',
                            ],
                        ],
                    ],
                ],
            ])
            ->addFieldset('profile', [
                'title' => 'Profile',
                'fields' => [
                    'avatar' => [
                        'type' => 'assets',
                        'container' => 'main',
                        'max_files' => 1,
                    ],
                    'hails_from' => [
                        'type' => 'partial',
                        'fieldset' => 'allegiances',
                    ],
                ],
            ])
            ->setFields([
                'name' => [
                    'type' => 'text',
                ],
                'address' => [
                    'type' => 'partial',
                    'fieldset' => 'profile',
                ],
            ])
            ->migrateContent([
                'name' => 'Joey Landreth',
                'avatar' => '/assets/img/stetson.jpg',
                'allegiances' => [
                    [
                        'country' => 'Canada',
                        'flag' => '/assets/img/canada.jpg',
                    ],
                ],
            ]);

        $expected = [
            'name' => 'Joey Landreth',
            'avatar' => 'img/stetson.jpg',
            'allegiances' => [
                [
                    'country' => 'Canada',
                    'flag' => 'img/canada.jpg',
                ],
            ],
            'blueprint' => 'speaker',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_can_migrate_bards_with_no_sets()
    {
        $content = $this
            ->setFields([
                'some_bard' => [
                    'type' => 'bard',
                    'buttons' => [
                        'bold',
                        'italic',
                    ],
                ],
            ])
            ->migrateContent([
                'some_bard' => 'Well hello there!',
            ]);

        $expected = [
            'some_bard' => 'Well hello there!',
            'blueprint' => 'speaker',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_can_migrate_custom_layout()
    {
        $content = $this->migrateContent([
            'layout' => 'custom',
            'test' => 'data',
        ]);

        $expected = [
            'layout' => 'custom',
            'test' => 'data',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_removes_default_layout()
    {
        $content = $this->migrateContent([
            'layout' => 'default',
            'test' => 'data',
        ]);

        $expected = [
            'test' => 'data',
        ];

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_removes_custom_default_layout()
    {
        $this->files->put(base_path('site/settings/theming.yaml'), 'default_layout: custom_default');

        $content = $this->migrateContent([
            'layout' => 'custom_default',
            'test' => 'data',
        ]);

        $expected = [
            'test' => 'data',
        ];

        $this->assertEquals($expected, $content);
    }
}
