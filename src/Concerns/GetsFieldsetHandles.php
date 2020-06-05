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
     * @return \stdClass
     */
    protected function getFieldsetHandles()
    {
        if (! File::exists(base_path('site/settings/fieldsets'))) {
            return (object) [
                'standard' => collect(),
                'partial' => collect(),
            ];
        }

        $fieldsets = collect(File::files(base_path('site/settings/fieldsets')))
            ->keyBy
            ->getFilenameWithoutExtension()
            ->map(function ($file) {
                return YAML::parse($file->getContents());
            });

        $partial = $fieldsets
            ->flatMap(function ($fieldset) {
                return $this->getPartialFieldsetHandles($fieldset);
            })
            ->values();

        $standard = $fieldsets
            ->keys()
            ->diff($partial)
            ->values();

        return (object) compact('standard', 'partial');
    }

    /**
     * Get partial fieldset handles.
     *
     * @param array $fieldset
     * @return array
     */
    protected function getPartialFieldsetHandles($fieldset)
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
            });
    }
}
