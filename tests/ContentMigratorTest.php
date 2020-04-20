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
                'image_in_section_two' => '/assets/img/coffee-mug.jpg',
            ]);

        $expected = [
            'image_in_section_one' => 'img/coffee-mug.jpg',
            'image_in_section_two' => 'img/coffee-mug.jpg',
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
}
