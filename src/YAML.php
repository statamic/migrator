<?php

namespace Statamic\Migrator;

use Spyc;
use Exception;
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
        if ($parser = static::detect()) {
            return static::{$parser}($str);
        }

        try {
            $parsed = static::symfony($str);
        } catch (Exception $exception) {
            $parsed = static::spyc($str);
        }

        return $parsed;
    }

    /**
     * Detect parser (symfony or spyc).
     *
     * @return bool
     */
    public static function detect()
    {
        if (File::exists($path = base_path('site/settings/system.yaml'))) {
            return preg_match('/yaml_parser:\s*symfony\s*$/', File::get($path), $matches)
                ? 'symfony'
                : 'spyc';
        }

        return null;
    }

    /**
     * Parse symfony yaml.
     *
     * @param array $str
     * @return array
     */
    public static function symfony($str)
    {
        return StatamicYAML::parse($str);
    }

    /**
     * Parse spyc yaml.
     *
     * @param string $str
     * @return array
     */
    public static function spyc($str)
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
