<?php

namespace Tests;

use Statamic\Migrator\YAML;

class MigrateFieldsetTest extends TestCase
{
    protected function paths($key = null)
    {
        $paths = [
            'new' => resource_path('fieldsets/post.yaml'),
            'old' => $this->sitePath('settings/fieldsets/post.yaml'),
            'fieldsets' => $this->sitePath('settings/fieldsets'),
            'system' => $this->sitePath('settings/system.yaml'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    /** @test */
    public function it_can_migrate_a_fieldset()
    {
        $fieldset = $this->migrateFieldset([
            'title' => 'Gallery',
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'width' => 50,
                ],
                'slug' => [
                    'type' => 'text',
                    'width' => 50,
                ],
            ],
        ]);

        $this->assertEquals($fieldset, [
            'title' => 'Gallery',
            'fields' => [
                [
                    'handle' => 'title',
                    'field' => [
                        'type' => 'text',
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'slug',
                    'field' => [
                        'type' => 'text',
                        'width' => 50,
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_assumes_type_text()
    {
        $fieldset = $this->migrateFieldset([
            'title' => 'Gallery',
            'fields' => [
                'title' => [
                    'width' => 50,
                ],
            ],
        ]);

        $this->assertEquals($fieldset, [
            'title' => 'Gallery',
            'fields' => [
                [
                    'handle' => 'title',
                    'field' => [
                        'type' => 'text',
                        'width' => 50,
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_migrate_nested_fields()
    {
        $fieldset = $this->migrateFieldset([
            'title' => 'Gallery',
            'fields' => [
                'prices' => [
                    'type' => 'grid',
                    'fields' => [
                        'label' => [
                            'type' => 'text',
                        ],
                        'cost' => [
                            'type' => 'currency',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertEquals($fieldset, [
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
                                    'type' => 'text',
                                ],
                            ],
                            [
                                'handle' => 'cost',
                                'field' => [
                                    'type' => 'currency',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_migrate_nested_sets_of_fields()
    {
        $fieldset = $this->migrateFieldset([
            'title' => 'Gallery',
            'fields' => [
                'prices' => [
                    'type' => 'replicator',
                    'sets' => [
                        'main' => [
                            'fields' => [
                                'label' => [
                                    'type' => 'text',
                                ],
                                'cost' => [
                                    'type' => 'currency',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertEquals($fieldset, [
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
                                            'type' => 'text',
                                        ],
                                    ],
                                    [
                                        'handle' => 'cost',
                                        'field' => [
                                            'type' => 'currency',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_migrate_empty_sets()
    {
        $fieldset = $this->migrateFieldset([
            'title' => 'Gallery',
            'fields' => [
                'prices' => [
                    'type' => 'replicator',
                    'sets' => [
                        'main' => [],
                    ],
                ],
            ],
        ]);

        $this->assertEquals($fieldset, [
            'title' => 'Gallery',
            'fields' => [
                [
                    'handle' => 'prices',
                    'field' => [
                        'type' => 'replicator',
                        'sets' => [
                            'main' => [
                                'fields' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_migrates_field_type_first()
    {
        $fieldset = $this->migrateFieldset([
            'title' => 'Gallery',
            'fields' => [
                'title' => [
                    'width' => 50,
                    'type' => 'text',
                ],
            ],
        ]);

        $this->assertEquals($fieldset, [
            'title' => 'Gallery',
            'fields' => [
                [
                    'handle' => 'title',
                    'field' => [
                        'type' => 'text',
                        'width' => 50,
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_migrates_field_conditions()
    {
        $fieldset = $this->migrateFieldset([
            'title' => 'Post',
            'fields' => [
                'author_name' => [
                    'type' => 'text',
                    'show_when' => [
                        'has_author' => 'not null',
                    ],
                ],
                'author_address' => [
                    'type' => 'text',
                    'show_when' => 'myCustomConditionMethod',
                ],
                'author_explain_yourself' => [
                    'type' => 'text',
                    'hide_when' => [
                        'favourite_food' => ['lasagna', 'pizza'],
                        'or_favourite_colour' => 'red',
                    ],
                ],
            ],
        ]);

        $this->assertEquals($fieldset, [
            'title' => 'Post',
            'fields' => [
                [
                    'handle' => 'author_name',
                    'field' => [
                        'type' => 'text',
                        'show_when' => [
                            'has_author' => 'not empty',
                        ],
                    ],
                ],
                [
                    'handle' => 'author_address',
                    'field' => [
                        'type' => 'text',
                        'show_when' => 'myCustomConditionMethod',
                    ],
                ],
                [
                    'handle' => 'author_explain_yourself',
                    'field' => [
                        'type' => 'text',
                        'hide_when_any' => [
                            'favourite_food' => 'contains_any lasagna, pizza',
                            'favourite_colour' => 'red',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_removes_hide_setting()
    {
        $fieldset = $this->migrateFieldset([
            'title' => 'Gallery',
            'hide' => true,
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'width' => 50,
                ],
            ],
        ]);

        $this->assertEquals($fieldset, [
            'title' => 'Gallery',
            'fields' => [
                [
                    'handle' => 'title',
                    'field' => [
                        'type' => 'text',
                        'width' => 50,
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_migrates_redactor_to_bard()
    {
        $fieldset = $this->migrateFieldset([
            'title' => 'Posts',
            'fields' => [
                'content' => [
                    'type' => 'redactor',
                ],
            ],
        ]);

        $this->assertEquals($fieldset, [
            'title' => 'Posts',
            'fields' => [
                [
                    'handle' => 'content',
                    'field' => [
                        'type' => 'bard',
                        'save_html' => true,
                        'buttons' => ['bold', 'italic', 'unorderedlist', 'orderedlist', 'html', 'quote', 'codeblock', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'anchor'],
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_migrates_redactor_with_custom_settings_to_bard()
    {
        $this->files->put($this->paths('system'), YAML::dump([
            'redactor' => [
                ['name' => 'Custom', 'settings' => ['buttons' => ['unorderedlist', 'orderedlist', 'h1']]],
            ],
        ]));

        $fieldset = $this->migrateFieldset([
            'title' => 'Posts',
            'fields' => [
                'content' => [
                    'type' => 'redactor',
                    'settings' => 'Custom',
                    'container' => 'main',
                ],
            ],
        ]);

        $this->assertEquals($fieldset, [
            'title' => 'Posts',
            'fields' => [
                [
                    'handle' => 'content',
                    'field' => [
                        'type' => 'bard',
                        'save_html' => true,
                        'container' => 'main',
                        'buttons' => ['unorderedlist', 'orderedlist', 'h1', 'image'],
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_migrates_pages_to_entries()
    {
        $fieldset = $this->migrateFieldset([
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

        $this->assertEquals($expected, $fieldset);
    }

    /** @test */
    public function it_migrates_collection_to_entries()
    {
        $fieldset = $this->migrateFieldset([
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
                'single_collection' => [
                    'type' => 'collection',
                    'max_items' => 1,
                    'collection' => 'blog',
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
                [
                    'handle' => 'single_collection',
                    'field' => [
                        'type' => 'entries',
                        'max_items' => 1,
                        'collections' => [
                            'blog',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $fieldset);
    }

    /** @test */
    public function it_migrates_taxonomy_to_terms()
    {
        $fieldset = $this->migrateFieldset([
            'title' => 'Posts',
            'fields' => [
                'tags' => [
                    'type' => 'taxonomy',
                    'max_items' => 1,
                    'taxonomy' => 'tags',
                ],
            ],
        ]);

        $expected = [
            'title' => 'Posts',
            'fields' => [
                [
                    'handle' => 'tags',
                    'field' => [
                        'type' => 'terms',
                        'max_items' => 1,
                        'taxonomies' => [
                            'tags',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $fieldset);
    }

    /** @test */
    public function it_migrates_option_based_suggest_to_select()
    {
        $fieldset = $this->migrateFieldset([
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

        $this->assertEquals($expected, $fieldset);
    }

    /** @test */
    public function it_migrates_option_based_suggest_to_multiple_select()
    {
        $fieldset = $this->migrateFieldset([
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

        $this->assertEquals($expected, $fieldset);
    }

    /** @test */
    public function it_migrates_native_mode_based_suggests_to_appropriate_relationship_fields()
    {
        $this->assertEquals(['type' => 'collections'], $this->migrateSuggestField(['mode' => 'collections']));
        $this->assertEquals(['type' => 'entries'], $this->migrateSuggestField(['mode' => 'collection']));
        $this->assertEquals(['type' => 'entries', 'collections' => ['pages']], $this->migrateSuggestField(['mode' => 'pages']));
        $this->assertEquals(['type' => 'taxonomy'], $this->migrateSuggestField(['mode' => 'taxonomy']));
        $this->assertEquals(['type' => 'form'], $this->migrateSuggestField(['mode' => 'form']));
        $this->assertEquals(['type' => 'users'], $this->migrateSuggestField(['mode' => 'users']));
        $this->assertEquals(['type' => 'user_groups'], $this->migrateSuggestField(['mode' => 'user_groups']));

        // And if they referenced using studly case...
        $this->assertEquals(['type' => 'collections'], $this->migrateSuggestField(['mode' => 'Collections']));
        $this->assertEquals(['type' => 'user_groups'], $this->migrateSuggestField(['mode' => 'UserGroups']));

        // And if it's a custom suggest mode, we'll leave it and throw a warning instead...
        $this->assertEquals(['type' => 'suggest', 'mode' => 'colours'], $this->migrateSuggestField(['mode' => 'colours']));
    }

    /** @test */
    public function it_migrates_partial_to_import()
    {
        $fieldset = $this->migrateFieldset([
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

        $this->assertEquals($expected, $fieldset);
    }

    /**
     * Temporary, until we implement sections in fieldsets!
     *
     * @test
n    */
    public function it_flattens_sections()
    {
        $this->files->put($this->paths('old'), <<<'EOT'
title: Address
sections:
  main:
    display: Main
    fields:
      street:
        type: text
        display: 'Street'
      province:
        type: text
        display: 'Province'
        width: 50
  secondary:
    display: Secondary
    fields:
      country:
        type: text
        display: 'Country'
        width: 50
EOT
);

        $expected = [
            'title' => 'Address',
            'fields' => [
                [
                    'handle' => 'street',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Street',
                    ],
                ],
                [
                    'handle' => 'province',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Province',
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'country',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Country',
                        'width' => 50,
                    ],
                ],
            ],
        ];

        $this->artisan('statamic:migrate:fieldset', ['handle' => 'post', '--force' => true]);

        $this->assertEquals($expected, YAML::parse($this->files->get($this->paths('new'))));
    }

    private function migrateFieldset($fieldsetConfig)
    {
        $this->files->put($this->paths('old'), YAML::dump($fieldsetConfig));

        $this->artisan('statamic:migrate:fieldset', ['handle' => 'post', '--force' => true]);

        return YAML::parse($this->files->get($this->paths('new')));
    }

    private function migrateSuggestField($suggestConfig)
    {
        $fieldset = $this->migrateFieldset([
            'title' => 'Posts',
            'fields' => [
                'test_suggest' => array_merge(['type' => 'suggest'], $suggestConfig),
            ],
        ]);

        return collect($fieldset['fields'])->first()['field'];
    }
}
