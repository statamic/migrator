<?php

namespace Statamic\Migrator;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Facades\Statamic\Console\Processes\Process;

class Configurator
{
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

        Process::run(['vendor/bin/php-cs-fixer', 'fix', $path, '--rules', $rules]);

        return $this;
    }

    /**
     * Set config value.
     *
     * @param string $key
     * @param string $value
     * @return return $this
     */
    public function set($key, $value, $allowFalsey = false)
    {
        $this->normalize();

        if (! $allowFalsey && $value === false) {
            return $this;
        }

        $value = $this->varExport($value);

        switch (true) {
            case $this->attemptToSetArrayValue($key, $value):
            case $this->attemptToSetStringValue($key, $value):
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
     * @param string $value
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
     * Attempt to set array config value.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function attemptToSetArrayValue($key, $value)
    {
        $config = $this->files->get($this->path());

        $isArrayValue = is_array($this->configGet($key));

        $regex = $this->buildPatternForKey('\X*', $key, $isArrayValue);

        preg_match($regex, $config, $matches);

        if (count($matches) != 3) {
            return false;
        }

        $updatedConfig = preg_replace($regex, "$1{$value}$2", $config);

        $this->files->put($this->path(), $updatedConfig);

        return true;
    }

    /**
     * Attempt to set string config value.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function attemptToSetStringValue($key, $value)
    {
        $config = $this->files->get($this->path());

        $regex = $this->buildPatternForKey('.*', $key);

        preg_match($regex, $config, $matches);

        if (count($matches) != 3) {
            return false;
        }

        $updatedConfig = preg_replace($regex, "$1{$value}$2", $config);

        $this->files->put($this->path(), $updatedConfig);

        return true;
    }

    /**
     * Attempt to set new config value.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function attemptToSetNewValue($key, $value)
    {
        $config = $this->files->get($this->path());

        $regex = '/(\];)/mU';

        preg_match($regex, $config, $matches);

        if (count($matches) != 2) {
            return false;
        }

        $element = $this->varExport($key) . ' => ' . $value;

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
     */
    protected function attemptToMergeIntoArray($key, $childKey, $value)
    {
        if ($this->configHasKey($fullKey = "{$key}.{$childKey}")) {
            return $this->set($fullKey, $value);
        }

        $config = $this->files->get($this->path());

        $regex = $this->buildPatternForKey('(\X*)', $key, false, true);

        preg_match($regex, $config, $matches);

        if (count($matches) != 4) {
            return false;
        }

        $element = $this->varExport($childKey) . ' => ' . $this->varExport($value) . ',';

        $updatedConfig = preg_replace($regex, "$1$2{$element}\n$3", $config);

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
        return config_path($this->configFile);
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
    public function buildPatternForKey($pattern, $key, $isArrayValue = false, $matchParentCloser = false)
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
    public function buildPatternForNestedKey($pattern, $key, $isArrayValue = false, $matchParentCloser = false)
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
     * @return string
     */
    protected function buildEndingGroup($isArrayValue, $indentation, $matchParent = false)
    {
        $endingGroup = $matchParent
            ? "(^\s{{$indentation}}]*,)"
            : '(,)';

        return $isArrayValue
            ? "\[\X*^\s{{$indentation}}\]{$endingGroup}"
            : $endingGroup;
    }

    /**
     * Export var as string.
     *
     * @param mixed $value
     * @return string
     */
    public function varExport($value)
    {
        // Utilize PHP's var_export.
        $value = var_export($value, true);

        // Ensure array starting bracket stays on same line as => operator.
        $value = str_replace("=> \n", '=> ', $value);

        // Remove numeric keys.
        $value = preg_replace("/(\s*)[0-9]+\s=>\s(.*)/", '$1$2', $value);

        return $value;
    }
}
