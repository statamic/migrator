<?php

namespace Tests;

use Facades\Statamic\Console\Processes\Process;
use Statamic\Facades\Path;
use Statamic\Migrator\Configurator;

class ConfiguratorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->files->copy(__DIR__.'/Fixtures/config/configurator-test.php', $this->path());

        $this->configurator = Configurator::file('statamic/configurator-test.php');

        Process::swap(new \Statamic\Console\Processes\Process(__DIR__.'/../'));
    }

    protected function path()
    {
        return Path::tidy(config_path('statamic/configurator-test.php'));
    }

    /** @test */
    public function it_normalizes_mangled_indentation_and_ensures_trailing_commas()
    {
        $this->configurator->normalize();

        $this->assertConfigFileContains(<<<'EOT'
    'disks_mangled' => [
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],
    ],
EOT
        );
    }

    /** @test */
    public function it_can_set_string_value_to_something_new()
    {
        $this->assertConfigFileContains(<<<'EOT'
    'action' => '!',
EOT
        );

        $this->configurator->set('action', '?');

        $this->assertConfigFileContains(<<<'EOT'
    'action' => '?',
EOT
        );

        $this->configurator
            ->set('action', [
                'hero' => 'batman',
            ])
            ->normalize();

        $this->assertConfigFileContains(<<<'EOT'
    'action' => [
        'hero' => 'batman',
    ],
EOT
        );
    }

    /** @test */
    public function it_can_set_integer_value_to_something_new()
    {
        $this->assertConfigFileContains(<<<'EOT'
    'pagination_size' => 50,
EOT
        );

        $this->configurator->set('pagination_size', 12);

        $this->assertConfigFileContains(<<<'EOT'
    'pagination_size' => 12,
EOT
        );
    }

    /** @test */
    public function it_can_set_array_value_to_something_new()
    {
        $this->assertConfigFileContains(<<<'EOT'
    'routes' => [
        // '/' => 'home'
    ],
EOT
        );

        $this->configurator->set('routes', [
            '/blog/feed' => [
                'layout' => 'feed',
                'template' => 'feeds/blog',
                'content_type' => 'atom',
            ],
        ]);

        $this->assertConfigFileContains(<<<'EOT'
    'routes' => [
        '/blog/feed' => [
            'layout' => 'feed',
            'template' => 'feeds/blog',
            'content_type' => 'atom',
        ],
    ],
EOT
        );

        $this->configurator->set('routes', '?');

        $this->assertConfigFileContains(<<<'EOT'
    'routes' => '?',
EOT
        );
    }

    /** @test */
    public function it_can_set_nested_array_value_to_something_new()
    {
        $this->assertConfigFileContains(<<<'EOT'
    'disks_spacious' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
EOT
        );

        $this->configurator->set('disks_spacious.local', [
            'driver' => 'not_local',
            'root' => 'not_root',
        ]);

        $this->assertConfigFileContains(<<<'EOT'
        'local' => [
            'driver' => 'not_local',
            'root' => 'not_root',
        ],
EOT
        );

        $this->configurator->set('disks_spacious.local.root', 'beer');

        $this->assertConfigFileContains(<<<'EOT'
        'local' => [
            'driver' => 'not_local',
            'root' => 'beer',
        ],
EOT
        );
    }

    /** @test */
    public function it_can_set_completely_new_values()
    {
        $this->configurator->set('lol', 'catz');

        $this->configurator->set('characters', [
            'best' => 'Kramer',
            'worst' => 'Newman',
        ]);

        $this->assertConfigFileContains(<<<'EOT'
    'extra-config' => [
        'from-some-other-package' => env('THIS_SHOULDNT_GET_TOUCHED'),
    ],

    'lol' => 'catz',

    'characters' => [
        'best' => 'Kramer',
        'worst' => 'Newman',
    ],

];
EOT
        );
    }

    /** @test */
    public function it_doesnt_set_if_falsey_value_is_passed()
    {
        $this->configurator->set('action', false);
        $this->configurator->set('action', '');
        $this->configurator->set('action', []);

        $this->assertConfigFileContains(<<<'EOT'
    'action' => '!',
EOT
        );
    }

    /** @test */
    public function it_does_set_falsey_value_when_explicitly_allowed()
    {
        $this->configurator->set('action', false, true);

        $this->assertConfigFileContains(<<<'EOT'
    'action' => false,
EOT
        );

        $this->configurator->set('action', '', true);

        $this->assertConfigFileContains(<<<'EOT'
    'action' => '',
EOT
        );

        $this->configurator->set('action', [], true);

        $this->assertConfigFileContains(<<<'EOT'
    'action' => [],
EOT
        );
    }

    /** @test */
    public function it_can_merge_into_array()
    {
        $this->assertConfigFileContains(<<<'EOT'
    'routes' => [
        // '/' => 'home'
    ],
EOT
        );

        $this->configurator->merge('routes', [
            '/search' => 'search',
            '/blog' => 'blog',
        ]);

        $this->assertConfigFileContains(<<<'EOT'
    'routes' => [
        // '/' => 'home'
        '/search' => 'search',
        '/blog' => 'blog',
    ],
EOT
        );

        $this->configurator->merge('routes', [
            '/blog' => 'new-blog',
            '/feed' => 'feed',
        ]);

        $this->assertConfigFileContains(<<<'EOT'
    'routes' => [
        // '/' => 'home'
        '/search' => 'search',
        '/blog' => 'new-blog',
        '/feed' => 'feed',
    ],
EOT
        );

        $this->configurator->merge('paths', [
            "public_path('assets')",
        ]);

        $this->assertConfigFileContains(<<<'EOT'
    'paths' => [
        public_path('images'),
        public_path('assets'),
    ],
EOT
        );
    }

    /** @test */
    public function it_can_merge_into_nested_array()
    {
        $this->assertConfigFileContains(<<<'EOT'
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
EOT
        );

        $this->configurator->merge('disks_spacious.local', [
            'root' => 'beer',
            'coca' => 'cola',
        ]);

        $this->assertConfigFileContains(<<<'EOT'
        'local' => [
            'driver' => 'local',
            'root' => 'beer',
            'coca' => 'cola',
        ],
EOT
        );
    }

    /** @test */
    public function it_can_spaciously_merge_into_array()
    {
        $this->assertConfigFileContains(<<<'EOT'
    'routes' => [
        // '/' => 'home'
    ],
EOT
        );

        $this->configurator->mergeSpaciously('routes', [
            '/search' => 'search',
            '/blog' => 'blog',
        ]);

        $this->assertConfigFileContains(<<<'EOT'
    'routes' => [
        // '/' => 'home'

        '/search' => 'search',

        '/blog' => 'blog',

    ],
EOT
        );

        $this->configurator->mergeSpaciously('routes', [
            '/blog' => 'new-blog',
            '/feed' => 'feed',
        ]);

        $this->assertConfigFileContains(<<<'EOT'
    'routes' => [
        // '/' => 'home'

        '/search' => 'search',

        '/blog' => 'new-blog',

        '/feed' => 'feed',

    ],
EOT
        );
    }

    /** @test */
    public function it_can_spaciously_merge_into_nested_array()
    {
        $this->assertConfigFileContains(<<<'EOT'
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
EOT
        );

        $this->configurator->mergeSpaciously('disks_spacious.local', [
            'root' => 'beer',
            'coca' => 'cola',
        ]);

        $this->assertConfigFileContains(<<<'EOT'
        'local' => [
            'driver' => 'local',
            'root' => 'beer',

            'coca' => 'cola',

        ],
EOT
        );
    }

    /** @test */
    public function it_can_spaciously_merge_into_mangled_array()
    {
        $this->assertConfigFileContains(<<<'EOT'
'disks_mangled' => [
's3'=>[
'driver'=>'s3',
'key'=>env('AWS_ACCESS_KEY_ID'),
'secret'   =>  env('AWS_SECRET_ACCESS_KEY'),
'region' => env('AWS_DEFAULT_REGION'),
'bucket' => env('AWS_BUCKET'),
'url' => env('AWS_URL')
]
EOT
        );

        $this->configurator->mergeSpaciously('disks_mangled', [
            'local' => [
                'driver' => 'local',
                'root' => "storage_path('app')",
            ],
        ]);

        $this->assertConfigFileContains(<<<'EOT'
    'disks_mangled' => [
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

    ],
EOT
        );
    }

    /** @test */
    public function it_can_refresh_config()
    {
        $this->configurator->merge('disks_spacious.local', [
            'root' => 'beer',
        ]);

        $this->assertNotEquals('beer', config('statamic.configurator-test.disks_spacious.local.root'));

        $this->configurator->refresh();

        $this->assertEquals('beer', config('statamic.configurator-test.disks_spacious.local.root'));
    }

    /** @test */
    public function it_wont_merge_duplicate_values()
    {
        $this->assertConfigFileContains(<<<'EOT'
    'routes' => [
        // '/' => 'home'
    ],
EOT
        );

        $this->assertConfigFileContains(<<<'EOT'
    'widgets' => [
        'getting_started',
    ],
EOT
        );

        $this->configurator->merge('routes', [
            '/search' => 'search',
            '/blog' => 'blog',
        ]);

        $this->configurator->mergeSpaciously('routes', [
            '/search' => 'search',
            '/pages' => 'pages',
        ]);

        $this->configurator->merge('widgets', [
            'getting_started',
            [
                'type' => 'collection',
                'collection' => 'posts',
                'limit' => 5,
            ],
        ]);

        $this->configurator->mergeSpaciously('widgets', [
            'getting_started',
            [
                'type' => 'collection',
                'collection' => 'things',
                'limit' => 3,
            ],
        ]);

        $this->assertConfigFileContains(<<<'EOT'
    'routes' => [
        // '/' => 'home'
        '/search' => 'search',
        '/blog' => 'blog',

        '/pages' => 'pages',

    ],
EOT
        );

        $this->assertConfigFileContains(<<<'EOT'
    'widgets' => [
        'getting_started',
        [
            'type' => 'collection',
            'collection' => 'posts',
            'limit' => 5,
        ],

        [
            'type' => 'collection',
            'collection' => 'things',
            'limit' => 3,
        ],

    ],
EOT
        );
    }

    /** @test */
    public function it_can_merge_multiple_array_items_with_children()
    {
        $this->configurator->merge('widgets', [
            [
                'type' => 'form',
                'form' => 'contact',
                'fields' => [
                    'name',
                    'email',
                ],
            ],
            [
                'type' => 'form',
                'form' => 'subscribe',
                'fields' => [
                    'name',
                    'email',
                ],
            ],
        ]);

        $this->configurator->mergeSpaciously('widgets', [
            [
                'type' => 'form',
                'form' => 'survey_1',
                'fields' => [
                    'name',
                    'email',
                ],
            ],
            [
                'type' => 'form',
                'form' => 'survey_2',
                'fields' => [
                    'name',
                    'email',
                ],
            ],
        ]);

        $this->assertConfigFileContains(<<<'EOT'
    'widgets' => [
        'getting_started',
        [
            'type' => 'form',
            'form' => 'contact',
            'fields' => [
                'name',
                'email',
            ],
        ],
        [
            'type' => 'form',
            'form' => 'subscribe',
            'fields' => [
                'name',
                'email',
            ],
        ],

        [
            'type' => 'form',
            'form' => 'survey_1',
            'fields' => [
                'name',
                'email',
            ],
        ],

        [
            'type' => 'form',
            'form' => 'survey_2',
            'fields' => [
                'name',
                'email',
            ],
        ],

    ],
EOT
        );
    }

    /**
     * Assert config file is valid and contains specific content.
     *
     * @param string $content
     */
    protected function assertConfigFileContains($content)
    {
        $contents = $this->files->get($path = config_path('statamic/configurator-test.php'));

        $beginning = <<<'EOT'
return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'start' => env('THIS_SHOULDNT_GET_TOUCHED'),
EOT;

        $extraConfig = <<<'EOT'
    'extra-config' => [
        'from-some-other-package' => env('THIS_SHOULDNT_GET_TOUCHED'),
    ],
EOT;

        $end = '];';

        // Assert valid PHP array.
        $this->assertEquals('array', gettype(include $path));

        // Assert begining and end of config is untouched.
        $this->assertStringContainsString($beginning, $contents);
        $this->assertStringContainsString($end, $contents);

        // Assert irrelevant config is untouched.
        $this->assertStringContainsString($extraConfig, $contents);

        // Assert config file contains specific content.
        return $this->assertStringContainsString($content, $contents);
    }
}
