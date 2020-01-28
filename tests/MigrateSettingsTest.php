<?php

namespace Tests;

use Tests\TestCase;
use Facades\Statamic\Console\Processes\Process;

class MigrateSettingsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Process::swap(new \Statamic\Console\Processes\Process(__DIR__ . '/../'));
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
        'en' => [
            'name' => 'English',
            'locale' => 'en_US',
            'url' => env('APP_URL'),
        ],

        'fr' => [
            'name' => 'French',
            'locale' => 'fr_FR',
            'url' => env('APP_URL_FR'),
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
