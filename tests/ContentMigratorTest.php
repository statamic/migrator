<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Migrator\YAML;
use Statamic\Migrator\ContentMigrator;
use Statamic\Migrator\FieldsetMigrator;

class ContentMigratorTest extends TestCase
{
    protected function path()
    {
        return base_path('site/settings/fieldsets/test_fieldset.yaml');
    }

    private function setFields($fields, $rawFieldset = false)
    {
        $this->assertFileNotExists($this->path());

        $fieldset = $rawFieldset ? $fields : [
            'sections' => [
                'main' => [
                    'fields' => $fields
                ]
            ]
        ];

        $this->files->put($this->path(), YAML::dump($fieldset));

        $this->assertFileExists($this->path());

        return $this;
    }

    private function migrateContent($content)
    {
        return ContentMigrator::usingFieldset('test_fieldset')->migrateContent($content);
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
                                    ]
                                ]
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
            ]
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
            ]
        ];

        $this->assertEquals($expected, $content);
    }
}
