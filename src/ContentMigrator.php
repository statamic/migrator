<?php

namespace Statamic\Migrator;

use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\YAML;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class ContentMigrator
{
    protected $fieldset;
    protected $fieldConfigs;

    /**
     * Instantiate content migrator.
     *
     * @param string $fieldset
     */
    public function __construct(string $fieldset)
    {
        $this->fieldset = $fieldset;

        $this->files = app(Filesystem::class);
    }

    /**
     * Instantiate content migrator.
     *
     * @param string $fieldset
     * @return static
     */
    public static function usingFieldset(string $fieldset)
    {
        return new static($fieldset);
    }

    /**
     * Migrate content.
     *
     * @param array $content
     * @return array
     */
    public function migrateContent($content)
    {
        $this->content = $content;

        $this
            ->getFieldConfigs()
            ->migrateFields()
            ->migrateFieldsetToBlueprint();

        return $this->content;
    }

    /**
     * Get flattened field configs from fieldset.
     *
     * @return $this
     */
    protected function getFieldConfigs()
    {
        $fieldsetPath = $this->sitePath("settings/fieldsets/{$this->fieldset}.yaml");

        if ($this->fieldset !== 'default' && ! $this->files->exists($fieldsetPath)) {
            // TODO: throw exception and/or handle warning?
            // throw new \Exception("Cannot find fieldset [{$this->fieldset}]");
        }

        $this->fieldConfigs = $this->files->exists($fieldsetPath)
            ? Arr::get(YAML::parse($this->files->get($fieldsetPath)), 'fields', [])
            : [];

        return $this;
    }

    /**
     * Migrate fields.
     *
     * @return $this
     */
    protected function migrateFields()
    {
        foreach ($this->content as $handle => $value) {
            $this->content[$handle] = $this->migrateField($handle, $value);
        }

        return $this;
    }

    /**
     * Migrate field.
     *
     * @param string $handle
     * @param mixed $value
     * @return mixed
     */
    protected function migrateField($handle, $value)
    {
        $fieldtype = $this->getFieldtype($handle);

        $migrateMethod = 'migrate' . ucfirst(strtolower($fieldtype)) . 'Field';

        if (method_exists($this, $migrateMethod)) {
            return $this->{$migrateMethod}($handle, $value, $this->getFieldConfig($handle));
        }

        return $value;
    }

    /**
     * Migrate fieldset to blueprint.
     *
     * @return $this
     */
    protected function migrateFieldsetToBlueprint()
    {
        if (isset($this->content['fieldset'])) {
            $this->content['blueprint'] = $this->content['fieldset'];
        }

        unset($this->content['fieldset']);

        return $this;
    }

    /**
     * Get site path.
     *
     * @param string|null $append
     * @return string
     */
    protected function sitePath($append = null)
    {
        return collect([base_path('site'), $append])->filter()->implode('/');
    }

    /**
     * Get fieldtype.
     *
     * @param string $handle
     */
    protected function getFieldtype($handle)
    {
        return $this->fieldConfigs[$handle]['type'] ?? 'text';
    }

    /**
     * Get field config.
     *
     * @param string $handle
     */
    protected function getFieldConfig($handle)
    {
        return $this->fieldConfigs[$handle] ?? [];
    }
}
