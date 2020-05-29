<?php

namespace Tests;

class MigrateFormTest extends TestCase
{
    protected function paths($key = null)
    {
        $paths = [
            'blueprint' => resource_path('blueprints/contact_form.yaml'),
            'form' => resource_path('forms/contact.yaml'),
            'submissions' => storage_path('forms/contact'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    /** @test */
    public function it_can_migrate_form()
    {
        $this->assertFileNotExists($this->paths('form'));

        $this->artisan('statamic:migrate:form', ['handle' => 'contact']);

        $this->assertFileExists($this->paths('form'));

        $expected = [
            'title' => 'Contact Me',
            'store' => false,
            'metrics' => [
                [
                    'type' => 'total',
                    'label' => 'Total Responses',
                ],
                [
                    'type' => 'sum',
                    'label' => 'Sum of Favorite Number',
                    'field' => 'number',
                ],
                [
                    'type' => 'average',
                    'label' => 'Average Favorite Number',
                    'field' => 'number',
                    'precision' => 1,
                ],
            ],
            'email' => [
                [
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'reply_to' => 'replyto@example.com',
                    'subject' => "You've got fan mail",
                    'template' => 'fan-mail',
                ],
            ],
            'blueprint' => 'contact_form',
        ];

        $this->assertParsedYamlEquals($expected, $this->paths('form'));
    }

    /** @test */
    public function it_can_migrate_fields_to_blueprint()
    {
        $this->assertFileNotExists($this->paths('blueprint'));

        $this->artisan('statamic:migrate:form', ['handle' => 'contact']);

        $this->assertFileExists($this->paths('blueprint'));

        $expected = [
            'title' => 'Contact Me',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Name',
                        'validate' => 'required|min:2',
                    ],
                ],
                [
                    'handle' => 'email',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Email Address',
                        'validate' => 'required|email',
                    ],
                ],
                [
                    'handle' => 'number',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Favorite Number',
                        'validate' => 'integer',
                    ],
                ],
                [
                    'handle' => 'comment',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Comment',
                        'listable' => false,
                    ],
                ],
            ],
        ];

        $this->assertParsedYamlEquals($expected, $this->paths('blueprint'));
    }

    /** @test */
    public function it_can_migrate_submissions()
    {
        $this->assertCount(0, $this->files->files($this->paths('submissions')));

        $this->artisan('statamic:migrate:form', ['handle' => 'contact']);

        $this->assertCount(2, $this->files->files($this->paths('submissions')));
    }
}
