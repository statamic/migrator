<?php

namespace Statamic\Migrator\Concerns;

use Illuminate\Support\Facades\File;
use Statamic\Migrator\YAML;
use Statamic\Support\Arr;

trait GetsFieldsetHandles
{
    /**
     * Get fieldset handles.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getFieldsetHandles()
    {
        if (! $this->files->exists($path = base_path('site/settings/fieldsets'))) {
            return collect();
        }

        return collect(File::files($path))
            ->keyBy
            ->getFilenameWithoutExtension()
            ->map(function ($file) {
                return YAML::parse($file->getContents());
            })
            ->flatMap(function ($fieldset) {
                return $this->getPartialFieldsetImports($fieldset);
            })
            ->unique()
            ->values();
    }

    /**
     * Get partial fieldset usages.
     *
     * @param array $fieldset
     * @return array
     */
    protected function getPartialFieldsetImports($fieldset)
    {
        $flattened = Arr::dot($fieldset);

        return collect($flattened)
            ->filter(function ($value, $key) {
                return preg_match('/fields.*type$/', $key) && $value === 'partial';
            })
            ->map(function ($value, $key) {
                return preg_replace('/(.*)\.type$/', '$1.fieldset', $key);
            })
            ->map(function ($fieldsetKey) use ($flattened) {
                return $flattened[$fieldsetKey];
            })
            ->values()
            ->all();
    }
}
