<?php

namespace Tests;

use Tests\TestCase;
use Facades\Statamic\Console\Processes\Process;

class MigrateSettingsTest extends TestCase
{
    protected function paths($key = null)
    {
        $paths = [
            'routesFile' => base_path('routes/web.php'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    public function setUp(): void
    {
        parent::setUp();

        Process::swap(new \Statamic\Console\Processes\Process(__DIR__ . '/../'));

        $this->files->copy(__DIR__ . '/Fixtures/routes/web.php', $this->paths('routesFile'));
    }

    /** @test */
    function it_migrates_cp_settings()
    {
        $this->assertConfigFileContains('cp.php', <<<EOT
        'getting_started',
EOT
        );

        $this->artisan('statamic:migrate:settings', ['handle' => 'cp']);

        $this->assertConfigFileContains('cp.php', <<<EOT
    'start_page' => 'collections',
EOT
        );

        $this->assertConfigFileContains('cp.php', <<<EOT
    'date_format' => 'Y-m-d',
EOT
        );

        $this->assertConfigFileContains('cp.php', <<<EOT
    'widgets' => [
        'getting_started',
        [
            'type' => 'collection',
            'collection' => 'blog',
            'limit' => 5,
        ],
        [
            'type' => 'collection',
            'collection' => 'things',
            'limit' => 5,
        ],
        [
            'type' => 'form',
            'form' => 'contact',
            'limit' => 5,
            'title' => 'Recent Submissions',
            'width' => 'full',
            'fields' => [
                'name',
                'email',
            ],
        ],
    ],
EOT
        );

        $this->assertConfigFileContains('cp.php', <<<EOT
    'pagination_size' => 12,
EOT
        );
    }

    /** @test */
    function it_migrates_routes()
    {
        $this->artisan('statamic:migrate:settings', ['handle' => 'routes']);

        $this->assertRoutesFileContains(<<<EOT
Route::statamic('search', 'search');
Route::statamic('blog/tags', 'blog.taxonomies');
Route::statamic('blog/feed', 'feeds.blog', [
    'layout' => 'feed',
    'content_type' => 'atom',
]);
Route::statamic('complicated/stuff', 'ComplicatedController@stuff');

Route::redirect('products', 'products-old');

Route::permanentRedirect('articles', '/');
Route::permanentRedirect('blog/posts', 'blog');
EOT
        );
    }

    /** @test */
    function it_migrates_system_settings()
    {
        $this->artisan('statamic:migrate:settings', ['handle' => 'system']);

        $this->assertConfigFileContains('sites.php', <<<EOT
    'sites' => [

        'default' => [
            'name' => 'English',
            'locale' => 'en_US',
            'url' => '/',
        ],

        'fr' => [
            'name' => 'French',
            'locale' => 'fr_CA',
            'url' => '/fr/',
        ],

    ],
EOT
        );
    }

    /** @test */
    function it_migrates_single_locale_to_default_site()
    {
        $this->files->put($this->sitePath('settings/system.yaml'), <<<EOT
locales:
  en:
    name: English
    full: en_US
    url: /
EOT
        );

        $this->artisan('statamic:migrate:settings', ['handle' => 'system']);

        $this->assertConfigFileContains('sites.php', <<<EOT
    'sites' => [

        'default' => [
            'name' => 'English',
            'locale' => 'en_US',
            'url' => '/',
        ],

    ],
EOT
        );
    }

    /** @test */
    function it_migrates_multiple_locales_with_env_references()
    {
        $this->files->put($this->sitePath('settings/system.yaml'), <<<EOT
locales:
  en:
    name: English
    full: en_US
    url: "{env:APP_URL}"
  fr:
    name: French
    full: fr_FR
    url: '{env:APP_URL_FR}'
EOT
        );

        $this->artisan('statamic:migrate:settings', ['handle' => 'system']);

        $this->assertConfigFileContains('sites.php', <<<EOT
    'sites' => [

        'default' => [
            'name' => 'English',
            'locale' => 'en_US',
            'url' => env('APP_URL'),
        ],

        'fr' => [
            'name' => 'French',
            'locale' => 'fr_FR',
            'url' => env('APP_URL_FR'),
        ],

    ],
EOT
        );
    }

    /** @test */
    function it_migrates_user_settings()
    {
        $this->artisan('statamic:migrate:settings', ['handle' => 'users']);

        $this->assertConfigFileContains('users.php', <<<EOT
    'avatars' => 'gravatar',
EOT
        );

        $this->assertConfigFileContains('users.php', <<<EOT
    'new_user_roles' => [
        'author',
    ],
EOT
        );
    }

    /** @test */
    function it_migrates_empty_user_settings()
    {
        $this->files->put($this->sitePath('settings/users.yaml'), <<<EOT
nothing_relevant: true
EOT
        );

        $this->artisan('statamic:migrate:settings', ['handle' => 'users']);

        $this->assertConfigFileContains('users.php', <<<EOT
    'avatars' => 'initials',
EOT
        );

        $this->assertConfigFileContains('users.php', <<<EOT
    'new_user_roles' => [
        //
    ],
EOT
        );
    }

    /**
     * Assert config file is valid and contains specific content.
     *
     * @param string $file
     * @param string $content
     */
    protected function assertConfigFileContains($file, $content)
    {
        $config = config_path("statamic/{$file}");

        $beginning = <<<EOT
<?php

return [
EOT;

        $end = '];';

        // Assert valid PHP array.
        $this->assertEquals('array', gettype(include $config));

        // Assert begining and end of config is untouched.
        $this->assertStringContainsString($beginning, $this->files->get($config));
        $this->assertStringContainsString($end, $this->files->get($config));

        // Assert config file contains specific content.
        return $this->assertStringContainsString($content, $this->files->get($config));
    }

    /**
     * Assert routes file contains specific content.
     *
     * @param string $content
     */
    protected function assertRoutesFileContains($content)
    {
        $contents = $this->files->get($this->paths('routesFile'));

        $beginning = <<<EOT
// Route::get('/', function () {
//     return view('welcome');
// });
EOT;

        $end = '];';

        // Assert begining of routes file is untouched.
        $this->assertStringContainsString($beginning, $contents);

        // Assert routes file contains specific content.
        return $this->assertStringContainsString($content, $contents);
    }
}
