<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Migrator\YAML;
use Tests\Console\Foundation\InteractsWithConsole;

class MigrateAssetContainerTest extends TestCase
{
    protected function paths()
    {
        return [
            base_path('content/assets'),
            public_path('assets'),
        ];
    }

    protected function containerPath($append = null)
    {
        return collect([base_path('content/assets'), $append])->filter()->implode('/');
    }

    /** @test */
    function it_migrates_yaml_config()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/site/content/assets', $this->containerPath());

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $expected = [
            'title' => 'Main Assets',
            'disk' => 'assets',
        ];

        $this->assertParsedYamlEquals($expected, $this->containerPath('main.yaml'));
    }
}
