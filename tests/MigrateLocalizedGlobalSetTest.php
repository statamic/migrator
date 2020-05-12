<?php

namespace Tests;

class MigrateLocalizedGlobalSetTest extends TestCase
{
    protected $siteFixture = 'site-localized';

    protected function paths($key = null)
    {
        $paths = [
            'set' => base_path('content/globals/global.yaml'),
            'default' => base_path('content/globals/default/global.yaml'),
            'fr' => base_path('content/globals/fr/global.yaml'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    /** @test */
    public function it_can_migrate_a_global_set()
    {
        $this->artisan('statamic:migrate:global-set', ['handle' => 'global']);

        $expectedSet = [
            'title' => 'Main Globals',
            'blueprint' => 'global',
        ];

        $expectedEnglish = [
            'site_name' => 'Redwood',
            'company' => 'Baller Inc',
            'author_name' => 'Niles Peppertrout',
        ];

        $expectedFrench = [
            'origin' => 'default',
            'site_name' => 'La Redwoody',
        ];

        $this->assertParsedYamlEquals($expectedSet, $this->paths('set'));
        $this->assertParsedYamlEquals($expectedEnglish, $this->paths('default'));
        $this->assertParsedYamlEquals($expectedFrench, $this->paths('fr'));
    }
}
