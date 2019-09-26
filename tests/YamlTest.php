<?php

namespace Tests;

use Exception;
use Tests\TestCase;
use Statamic\Migrator\YAML;

class YamlTest extends TestCase
{
    public function path()
    {
        return base_path('site/settings/system.yaml');
    }

    /** @test */
    function it_detects_parser()
    {
        $this->assertFileNotExists($this->path());
        $this->assertEquals('spyc', YAML::detectParser());

        $this->files->put($this->path(), "yaml_parser: spyc\n");
        $this->assertEquals('spyc', YAML::detectParser());

        $this->files->put($this->path(), "yaml_parser: symfony\n");
        $this->assertEquals('symfony', YAML::detectParser());
    }

    /** @test */
    function it_defers_to_statamic_yaml_facade_when_dumping()
    {
        $data = ['description' => '@seo:pro'];

        $this->assertEquals("description: '@seo:pro'\n", Yaml::dump($data));
    }

    /** @test */
    function it_parses_symfonic_yaml()
    {
        $yaml = "description: '@seo:pro'\n";

        $this->files->put($this->path(), "yaml_parser: symfony\n");

        $this->assertEquals(['description' => '@seo:pro'], Yaml::parse($yaml));
    }

    /** @test */
    function it_parses_spicey_yaml()
    {
        $yaml = "description: @seo:pro\n";

        $this->assertEquals(['description' => '@seo:pro'], Yaml::parse($yaml));

        $this->files->put($this->path(), "yaml_parser: spyc\n");

        $this->assertEquals(['description' => '@seo:pro'], Yaml::parse($yaml));
    }

    /** @test */
    function it_cannot_parse_spicey_yaml_if_set_to_symfony()
    {
        $yaml = "description: @seo:pro\n";

        $this->files->put($this->path(), "yaml_parser: symfony\n");

        $this->expectException(Exception::class);

        YAML::parse($yaml);
    }
}
