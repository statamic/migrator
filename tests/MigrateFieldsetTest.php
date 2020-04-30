<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Migrator\YAML;

class MigrateFieldsetTest extends TestCase
{
    protected function paths($key = null)
    {
        $paths = [
            'new' => resource_path('blueprints/post.yaml'),
            'old' => $this->sitePath('settings/fieldsets/post.yaml'),
            'fieldsets' => $this->sitePath('settings/fieldsets'),
            'system' => $this->sitePath('settings/system.yaml'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    private function migrateFieldsetToBlueprint($fieldsetConfig)
    {
        $this->files->put($this->paths('old'), YAML::dump($fieldsetConfig));

        $this->artisan('statamic:migrate:fieldset', ['handle' => 'post']);

        return YAML::parse($this->files->get($this->paths('new')));
    }

    /** @test */
    function it_can_migrate_a_fieldset_to_a_blueprint()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Gallery',
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'width' => 50
                ],
                'slug' => [
                    'type' => 'text',
                    'width' => 50
                ]
            ]
        ]);

        $this->assertEquals($blueprint, [
            'title' => 'Gallery',
            'fields' => [
                [
                    'handle' => 'title',
                    'field' => [
                        'type' => 'text',
                        'width' => 50
                    ]
                ],
                [
                    'handle' => 'slug',
                    'field' => [
                        'type' => 'text',
                        'width' => 50
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    function it_assumes_type_text()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Gallery',
            'fields' => [
                'title' => [
                    'width' => 50
                ]
            ]
        ]);

        $this->assertEquals($blueprint, [
            'title' => 'Gallery',
            'fields' => [
                [
                    'handle' => 'title',
                    'field' => [
                        'type' => 'text',
                        'width' => 50
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    function it_can_migrate_nested_fields()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Gallery',
            'fields' => [
                'prices' => [
                    'type' => 'grid',
                    'fields' => [
                        'label' => [
                            'type' => 'text'
                        ],
                        'cost' => [
                            'type' => 'currency'
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertEquals($blueprint, [
            'title' => 'Gallery',
            'fields' => [
                [
                    'handle' => 'prices',
                    'field' => [
                        'type' => 'grid',
                        'fields' => [
                            [
                                'handle' => 'label',
                                'field' => [
                                    'type' => 'text'
                                ]
                            ],
                            [
                                'handle' => 'cost',
                                'field' => [
                                    'type' => 'currency'
                                ]
                            ],
                        ]
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    function it_can_migrate_nested_sets_of_fields()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Gallery',
            'fields' => [
                'prices' => [
                    'type' => 'replicator',
                    'sets' => [
                        'main' => [
                            'fields' => [
                                'label' => [
                                    'type' => 'text'
                                ],
                                'cost' => [
                                    'type' => 'currency'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertEquals($blueprint, [
            'title' => 'Gallery',
            'fields' => [
                [
                    'handle' => 'prices',
                    'field' => [
                        'type' => 'replicator',
                        'sets' => [
                            'main' => [
                                'fields' => [
                                    [
                                        'handle' => 'label',
                                        'field' => [
                                            'type' => 'text'
                                        ]
                                    ],
                                    [
                                        'handle' => 'cost',
                                        'field' => [
                                            'type' => 'currency'
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    function it_migrates_field_type_first()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Gallery',
            'fields' => [
                'title' => [
                    'width' => 50,
                    'type' => 'text'
                ]
            ]
        ]);

        $this->assertEquals($blueprint, [
            'title' => 'Gallery',
            'fields' => [
                [
                    'handle' => 'title',
                    'field' => [
                        'type' => 'text',
                        'width' => 50
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    function it_migrates_field_conditions()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Post',
            'fields' => [
                'author_name' => [
                    'type' => 'text',
                    'show_when' => [
                        'has_author' => 'not null'
                    ],
                ],
                'author_address' => [
                    'type' => 'text',
                    'show_when' => 'myCustomConditionMethod'
                ],
                'author_explain_yourself' => [
                    'type' => 'text',
                    'hide_when' => [
                        'favourite_food' => ['lasagna', 'pizza'],
                        'or_favourite_colour' => 'red'
                    ]
                ]
            ]
        ]);

        $this->assertEquals($blueprint, [
            'title' => 'Post',
            'fields' => [
                [
                    'handle' => 'author_name',
                    'field' => [
                        'type' => 'text',
                        'show_when' => [
                            'has_author' => 'not empty'
                        ]
                    ]
                ],
                [
                    'handle' => 'author_address',
                    'field' => [
                        'type' => 'text',
                        'show_when' => 'myCustomConditionMethod'
                    ]
                ],
                [
                    'handle' => 'author_explain_yourself',
                    'field' => [
                        'type' => 'text',
                        'hide_when_any' => [
                            'favourite_food' => 'contains_any lasagna, pizza',
                            'favourite_colour' => 'red'
                        ]
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    function it_removes_hide_setting()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Gallery',
            'hide' => true,
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'width' => 50
                ]
            ]
        ]);

        $this->assertEquals($blueprint, [
            'title' => 'Gallery',
            'fields' => [
                [
                    'handle' => 'title',
                    'field' => [
                        'type' => 'text',
                        'width' => 50
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    function it_migrates_redactor_to_bard()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Posts',
            'fields' => [
                'content' => [
                    'type' => 'redactor',
                ]
            ]
        ]);

        $this->assertEquals($blueprint, [
            'title' => 'Posts',
            'fields' => [
                [
                    'handle' => 'content',
                    'field' => [
                        'type' => 'bard',
                        'save_html' => true,
                        'buttons' => ['bold', 'italic', 'unorderedlist', 'orderedlist', 'html', 'quote', 'codeblock', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'anchor'],
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    function it_migrates_redactor_with_custom_settings_to_bard()
    {
        $this->files->put($this->paths('system'), YAML::dump([
            'redactor' => [
                ['name' => 'Custom', 'settings' => ['buttons' => ['unorderedlist', 'orderedlist', 'h1']]],
            ]
        ]));

        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Posts',
            'fields' => [
                'content' => [
                    'type' => 'redactor',
                    'settings' => 'Custom',
                    'container' => 'main',
                ]
            ]
        ]);

        $this->assertEquals($blueprint, [
            'title' => 'Posts',
            'fields' => [
                [
                    'handle' => 'content',
                    'field' => [
                        'type' => 'bard',
                        'save_html' => true,
                        'container' => 'main',
                        'buttons' => ['unorderedlist', 'orderedlist', 'h1', 'image'],
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    function it_migrates_pages_to_entries()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Posts',
            'fields' => [
                'related' => [
                    'type' => 'pages',
                    'max_items' => 1,
                ],
            ],
        ]);

        $expected = [
            'title' => 'Posts',
            'fields' => [
                [
                    'handle' => 'related',
                    'field' => [
                        'type' => 'entries',
                        'max_items' => 1,
                        'collections' => [
                            'pages',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $blueprint);
    }

    /** @test */
    function it_migrates_collection_to_entries()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Posts',
            'fields' => [
                'related' => [
                    'type' => 'collection',
                    'max_items' => 1,
                    'collection' => [
                        'blog',
                        'products',
                    ],
                ],
            ],
        ]);

        $expected = [
            'title' => 'Posts',
            'fields' => [
                [
                    'handle' => 'related',
                    'field' => [
                        'type' => 'entries',
                        'max_items' => 1,
                        'collections' => [
                            'blog',
                            'products',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $blueprint);
    }

    /** @test */
    function it_migrates_option_based_suggest_to_select()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Posts',
            'fields' => [
                'colours' => [
                    'type' => 'suggest',
                    'max_items' => 1,
                    'create' => true,
                    'options' => [
                        'red' => 'Red',
                        'blue' => 'Blue',
                    ],
                ],
            ],
        ]);

        $expected = [
            'title' => 'Posts',
            'fields' => [
                [
                    'handle' => 'colours',
                    'field' => [
                        'type' => 'select',
                        'max_items' => 1,
                        'taggable' => true,
                        'options' => [
                            'red' => 'Red',
                            'blue' => 'Blue',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $blueprint);
    }

    /** @test */
    function it_migrates_option_based_suggest_to_multiple_select()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Posts',
            'fields' => [
                'colours' => [
                    'type' => 'suggest',
                    'max_items' => 3,
                    'options' => [
                        'red' => 'Red',
                        'blue' => 'Blue',
                    ],
                ],
            ],
        ]);

        $expected = [
            'title' => 'Posts',
            'fields' => [
                [
                    'handle' => 'colours',
                    'field' => [
                        'type' => 'select',
                        'max_items' => 3,
                        'multiple' => true,
                        'options' => [
                            'red' => 'Red',
                            'blue' => 'Blue',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $blueprint);
    }

    /** @test */
    function it_migrates_partial_to_import()
    {
        $blueprint = $this->migrateFieldsetToBlueprint([
            'title' => 'Posts',
            'fields' => [
                'name' => [
                    'type' => 'text',
                ],
                'address' => [
                    'type' => 'partial',
                    'fieldset' => 'address',
                ],
            ],
        ]);

        $expected = [
            'title' => 'Posts',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => [
                        'type' => 'text',
                    ],
                ],
                [
                    'import' => 'address',
                ],
            ],
        ];

        $this->assertEquals($expected, $blueprint);
    }
}
