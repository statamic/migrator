<?php

namespace Statamic\Migrator;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Facades\Path;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\VarExporter\VarExporter;
use Facades\Statamic\Console\Processes\Process;

class Configurator
{
    use Concerns\PreparesPhpCsFixer;

    protected $configFile;
    protected $files;

    /**
     * Instantiate configurator.
     *
     * @param string $configFile
     */
    public function __construct($configFile)
    {
        $this->configFile = $configFile;

        $this->files = app(Filesystem::class);
    }

    /**
     * Instantiate configurator.
     *
     * @param string $configFile
     * @return static
     */
    public static function file($configFile)
    {
        return new static($configFile);
    }

    /**
     * Normalize config file.
     *
     * @return $this
     */
    public function normalize()
    {
        $path = config_path($this->configFile);

        $rules = json_encode([
            '@PSR2' => true,
            'array_indentation' => true,
            'array_syntax' => ['syntax' => 'short'],
            'trailing_comma_in_multiline_array' => true,
            'binary_operator_spaces' => true,
        ]);

        $this->preparePhpCsFixer();

        Process::run(['vendor/bin/php-cs-fixer', 'fix', $path, '--rules', $rules]);

        return $this;
    }

    /**
     * Set config value.
     *
     * @param string $key
     * @param string $value
     * @param bool $allowFalsey
     * @return return $this
     */
    public function set($key, $value, $allowFalsey = false)
    {
        $this->normalize();

        if (! $allowFalsey && $value === false) {
            return $this;
        }

        switch (true) {
            case $this->attemptToSetArrayValue($key, $value):
            case $this->attemptToSetNonArrayValue($key, $value):
            case $this->attemptToSetNewValue($key, $value):
                return $this->normalize();
            default:
                throw new \Exception('Could not set config value');
        }

        return $this;
    }

    /**
     * Merge into array config.
     *
     * @param string $key
     * @param array $value
     * @return return $this
     */
    public function merge($key, $items)
    {
        $this->normalize();

        foreach ($items as $childKey => $value) {
            $this->attemptToMergeIntoArray($key, $childKey, $value);
        }

        return $this->normalize();
    }

    /**
     * Merge spaciously into array config.
     *
     * @param string $key
     * @param array $value
     * @return return $this
     */
    public function mergeSpaciously($key, $items)
    {
        $this->normalize();

        foreach ($items as $childKey => $value) {
            $this->attemptToMergeIntoArray($key, $childKey, $value, true);
        }

        return $this->normalize();
    }

    /**
     * Refresh config, since we manually inject new config directly into the PHP file.
     *
     * @return $this
     */
    public function refresh()
    {
        $key = str_replace(Path::resolve(config_path()) . '/', '', $this->path());
        $key = str_replace('.php', '', $key);
        $key = str_replace('/', '.', $key);

        $updatedConfig = include $this->path();

        config([$key => $updatedConfig]);

        return $this;
    }

    /**
     * Attempt to set array config value.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    protected function attemptToSetArrayValue($key, $value)
    {
        $config = $this->files->get($this->path());

        $isArrayValue = is_array($this->configGet($key));

        $regex = $this->buildPatternForKey('\[\X*', $key, $isArrayValue);

        preg_match($regex, $config, $matches);

        if (count($matches) != 3) {
            return false;
        }

        $value = $this->varExport($value);

        $updatedConfig = preg_replace($regex, "$1{$value}$2", $config);

        $this->files->put($this->path(), $updatedConfig);

        return true;
    }

    /**
     * Attempt to set non-array config value.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    protected function attemptToSetNonArrayValue($key, $value)
    {
        $config = $this->files->get($this->path());

        $regex = $this->buildPatternForKey('.*', $key);

        preg_match($regex, $config, $matches);

        if (count($matches) != 3) {
            return false;
        }

        $value = $this->varExport($value);

        $updatedConfig = preg_replace($regex, '${1}' . $value . '$2', $config);

        $this->files->put($this->path(), $updatedConfig);

        return true;
    }

    /**
     * Attempt to set new config value.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    protected function attemptToSetNewValue($key, $value)
    {
        $config = $this->files->get($this->path());

        $regex = '/(\];)/mU';

        preg_match($regex, $config, $matches);

        if (count($matches) != 2) {
            return false;
        }

        $element = $this->varExport($key) . ' => ' . $this->varExport($value);

        $updatedConfig = preg_replace($regex, "{$element}\n\n$1", $config);

        $this->files->put($this->path(), $updatedConfig);

        return true;
    }

    /**
     * Attempt to merge array config value.
     *
     * @param string $key
     * @param string $childKey
     * @param mixed $value
     * @param bool $spaciously
     * @return bool
     */
    protected function attemptToMergeIntoArray($key, $childKey, $value, $spaciously = false)
    {
        if (is_string($childKey) && $this->configHasKey($fullKey = "{$key}.{$childKey}")) {
            return $this->set($fullKey, $value);
        } elseif (! is_string($childKey) && $this->configHasArrayValue($key, $value)) {
            return false;
        }

        $config = $this->files->get($this->path());

        $regex = $this->buildPatternForKey('(\X*)', $key, false, true);

        preg_match($regex, $config, $matches);

        if (count($matches) != 4) {
            return false;
        }

        $element = is_string($childKey)
            ? $this->varExport($childKey) . ' => ' . $this->varExport($value) . ','
            : $this->varExport($value) . ',';

        $element = $spaciously ? "\n{$element}\n" : $element;

        $updatedConfig = preg_replace($regex, "$1$2\n{$element}\n$3", $config);

        $this->files->put($this->path(), $updatedConfig);

        return true;
    }

    /**
     * Determine if config already has key.
     *
     * @param string $key
     * @return bool
     */
    protected function configHasKey($key)
    {
        return Arr::has(include $this->path(), $key);
    }

    /**
     * Determine if config already has array value.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    protected function configHasArrayValue($key, $value)
    {
        return in_array($value, $this->configGet($key));
    }

    /**
     * Get from config by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function configGet($key, $default = null)
    {
        return Arr::get(include $this->path(), $key, $default);
    }

    /**
     * Get full config file path.
     *
     * @return string
     */
    protected function path()
    {
        return Path::resolve(config_path($this->configFile));
    }

    /**
     * Build pattern for config key.
     *
     * @param string $pattern
     * @param string $key
     * @param bool $isArrayValue
     * @param bool $matchParentCloser
     * @return string
     */
    protected function buildPatternForKey($pattern, $key, $isArrayValue = false, $matchParentCloser = false)
    {
        if (Str::contains($key, '.')) {
            return $this->buildPatternForNestedKey($pattern, $key, $isArrayValue, $matchParentCloser);
        }

        $indentation = 4;

        $beginningGroup = "(^\s{{$indentation}}['\"]{$key}['\"]\s\=\>\s)";

        $endingGroup = $this->buildEndingGroup($isArrayValue, $indentation, $matchParentCloser);

        $pattern = str_replace('/', '\/', $beginningGroup . $pattern . $endingGroup);

        return "/{$pattern}/mU";
    }

    /**
     * Build pattern for nested config key.
     *
     * @param string $pattern
     * @param string $key
     * @param bool $isArrayValue
     * @param bool $matchParentCloser
     * @return string
     */
    protected function buildPatternForNestedKey($pattern, $key, $isArrayValue = false, $matchParentCloser = false)
    {
        $indentation = 0;

        $beginningRegex = collect(explode('.', $key))
            ->map(function ($key) use (&$indentation) {
                $indentation = $indentation + 4;
                return "^\s{{$indentation}}['\"]{$key}['\"]\s\=\>\s";
            })
            ->implode('\X*');

        $beginningGroup = "({$beginningRegex})";

        $endingGroup = $this->buildEndingGroup($isArrayValue, $indentation, $matchParentCloser);

        $pattern = str_replace('/', '\/', $beginningGroup . $pattern . $endingGroup);

        return "/{$pattern}/mU";
    }

    /**
     * Build ending group.
     *
     * @param bool $isArrayValue
     * @param int $indentation
     * @param bool $matchParent
     * @return string
     */
    protected function buildEndingGroup($isArrayValue, $indentation, $matchParent = false)
    {
        $endingGroup = $matchParent
            ? "\n*(^\s{{$indentation}}]*,)"
            : '(,)';

        return $isArrayValue
            ? "\X*^\s{{$indentation}}\]{$endingGroup}"
            : $endingGroup;
    }

    /**
     * Export var as string.
     *
     * @param mixed $value
     * @return string
     */
    protected function varExport($value)
    {
        $value = VarExporter::export($value);

        // Remove numeric keys.
        $value = preg_replace("/(\s*)[0-9]+\s=>\s(.*)/", '$1$2', $value);

        // Ensure dynamic function stubs are not treated as strings.
        $value = preg_replace("/(.*=> )'([_a-zA-Z]*\()([^()]*)(\))'(.*)/", '$1$2$3$4$5', $value);

        // Ensure env interpolations are converted to PHP helpers.
        $value = preg_replace("/[\"']\{env:(.*)\}[\"']/", "env('$1')", $value);

        // Unescape single quotes.
        $value = str_replace("\'", "'", $value);

        return $value;
    }
}
