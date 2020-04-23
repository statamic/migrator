<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Migrator\YAML;

class MigrateFieldsetPartialtest extends TestCase
{
    protected function paths($key = null)
    {
        $paths = [
            'new_fieldset' => resource_path('fieldsets/address.yaml'),
            'new_blueprint' => resource_path('blueprints/address.yaml'),
            'old' => $this->sitePath('settings/fieldsets/address.yaml'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    /** @test */
    function it_migrates_fieldset_partial_to_v3_fieldset()
    {
        $this->assertFileNotExists($this->paths('new_fieldset'));

        $this->artisan('statamic:migrate:fieldset-partial', ['handle' => 'address']);

        $this->assertFileExists($this->paths('new_fieldset'));

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

        $this->assertEquals($expected, YAML::parse($this->files->get($this->paths('new_fieldset'))));
    }

    /** @test */
    function it_migrates_fieldset_partial_to_blueprint_wrapper()
    {
        $this->assertFileNotExists($this->paths('new_blueprint'));

        $this->artisan('statamic:migrate:fieldset-partial', ['handle' => 'address']);

        $this->assertFileExists($this->paths('new_blueprint'));

        $expected = [
            'title' => 'Address',
            'fields' => [
                [
                    'import' => 'address',
                ],
            ],
        ];

        $this->assertEquals($expected, YAML::parse($this->files->get($this->paths('new_blueprint'))));
    }
}
