<?php

namespace Tests;

use Tests\TestCase;
use Facades\Statamic\Console\Processes\Process;
use Tests\Console\Foundation\InteractsWithConsole;

class MigrateSettingsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->files->deleteDirectory($this->path());
        $this->files->copyDirectory('vendor/statamic/cms/config', $this->path());

        Process::swap(new \Statamic\Console\Processes\Process(__DIR__ . '/../'));
    }

    protected function path($append = null)
    {
        return collect([config_path('statamic'), $append])->filter()->implode('/');
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

        // $this->assertConfigFileContains('cp.php', <<<EOT
    // 'pagination_size' => 12,
// EOT
        // );
    }

    /** @test */
    function it_migrates_routes()
    {
        $this->artisan('statamic:migrate:settings', ['handle' => 'routes']);

        $this->assertConfigFileContains('routes.php', <<<EOT
    'routes' => [
        // '/' => 'home'
        '/search' => 'search',
        '/blog/tags' => 'blog/taxonomies',
        '/blog/feed' => [
            'layout' => 'feed',
            'template' => 'feeds/blog',
            'content_type' => 'atom',
        ],
    ],
EOT
        );

        $this->assertConfigFileContains('routes.php', <<<EOT
    'vanity' => [
        // '/promo' => '/blog/2019/09/big-sale-on-hot-dogs',
        '/products' => '/products-old',
    ],
EOT
        );

        $this->assertConfigFileContains('routes.php', <<<EOT
    'redirects' => [
        // '/here' => '/there',
        '/articles' => '/',
        '/blog/posts' => '/blog',
    ],
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
            'name' => config('app.name'),
            'locale' => 'en_US',
            'url' => '/',
        ],

        'en' => [
            'name' => 'English',
            'locale' => 'en_US',
            'url' => '/',
        ],

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
        $this->assertContains($beginning, $this->files->get($config));
        $this->assertContains($end, $this->files->get($config));

        // Assert config file contains specific content.
        return $this->assertContains($content, $this->files->get($config));
    }
}
