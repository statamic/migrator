<?php

namespace Statamic\Migrator;

use Spyc;
use Statamic\Facades\File;
use Statamic\Facades\Pattern;
use Statamic\Facades\YAML as StatamicYAML;

class YAML
{
    /**
     * Parse yaml.
     *
     * @param string $str
     * @return array
     */
    public static function parse($str)
    {
        if (static::detectParser() === 'spyc') {
            return static::parseSpyc($str);
        }

        return StatamicYAML::parse($str);
    }

    /**
     * Detect parser (symfony or spyc).
     *
     * @return bool
     */
    public static function detectParser()
    {
        if (File::exists($path = base_path('site/settings/system.yaml'))) {
            if (preg_match('/yaml_parser:\s*symfony\s*$/', File::get($path), $matches)) {
                return 'symfony';
            }
        }

        return 'spyc';
    }

    /**
     * Parse spyc yaml.
     *
     * @param string $str
     * @return array
     */
    public static function parseSpyc($str)
    {
        if (empty($str)) {
            return [];
        }

        if (Pattern::startsWith($str, '---')) {
            $split = preg_split("/\n---/", $str, 2, PREG_SPLIT_NO_EMPTY);
            $str = $split[0];
            $content = ltrim(array_get($split, 1, ''));
        }

        $yaml = Spyc::YAMLLoadString($str);

        return isset($content)
            ? $yaml + ['content' => $content]
            : $yaml;
    }

    /**
     * Defer all other calls to Statamic's YAML facade.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return StatamicYAML::{$name}(...$arguments);
    }
}
