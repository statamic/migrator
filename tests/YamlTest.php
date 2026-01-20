<?php

namespace Tests;

use Exception;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Path;
use Statamic\Migrator\YAML;

class YamlTest extends TestCase
{
    public function path()
    {
        return Path::tidy(base_path('site/settings/system.yaml'));
    }

    #[Test]
    public function it_detects_parser()
    {
        $this->files->delete($this->path());
        $this->assertFileNotExists($this->path());
        $this->assertEquals(null, YAML::detect());

        $this->files->put($this->path(), "yaml_parser: spyc\n");
        $this->assertEquals('spyc', YAML::detect());

        $this->files->put($this->path(), "yaml_parser: symfony\n");
        $this->assertEquals('symfony', YAML::detect());
    }

    #[Test]
    public function it_parses_symfonic_yaml()
    {
        $yaml = "description: '@seo:pro'\n";

        $this->files->put($this->path(), "yaml_parser: symfony\n");

        $this->assertEquals(['description' => '@seo:pro'], Yaml::parse($yaml));
    }

    #[Test]
    public function it_parses_spicey_yaml()
    {
        $yaml = "description: @seo:pro\n";

        $this->files->put($this->path(), "yaml_parser: spyc\n");

        $this->assertEquals(['description' => '@seo:pro'], Yaml::parse($yaml));
    }

    #[Test]
    public function it_cannot_parse_spicey_yaml_if_site_settings_explicitly_set_to_symfony()
    {
        $yaml = "description: @seo:pro\n";

        $this->files->put($this->path(), "yaml_parser: symfony\n");

        $this->expectException(Exception::class);

        YAML::parse($yaml);
    }

    #[Test]
    public function it_falls_back_to_spicey_parsing_if_necesary_when_it_cannot_detect_site_settings()
    {
        $this->assertEquals(['description' => '@seo:pro'], Yaml::parse("description: '@seo:pro'\n"));
        $this->assertEquals(['description' => '@seo:pro'], Yaml::parse("description: @seo:pro\n"));
    }

    #[Test]
    public function it_defers_to_statamic_yaml_facade_when_dumping()
    {
        $data = ['description' => '@seo:pro'];

        $this->assertEquals("description: '@seo:pro'\n", Yaml::dump($data));
    }
}
