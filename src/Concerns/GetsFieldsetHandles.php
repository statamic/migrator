<?php

namespace Statamic\Migrator\Concerns;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Migrator\YAML;
use Illuminate\Support\Facades\File;

trait GetsFieldsetHandles
{
    /**
     * Get fieldset handles
     *
     * @return \stdClass
     */
    protected function getFieldsetHandles()
    {
        $fieldsets = collect(File::files(base_path('site/settings/fieldsets')))
            ->keyBy->getFilenameWithoutExtension()
            ->map(function ($file) {
                return YAML::parse($file->getContents());
            });

        $imported = $fieldsets
            ->flatMap(function ($fieldset) {
                return $this->getImportedFieldsetHandles($fieldset);
            })
            ->values();

        $nonImported = $fieldsets
            ->keys()
            ->diff($imported)
            ->values();

        return (object) compact('imported', 'nonImported');
    }

    /**
     * Get imported fieldset handles.
     *
     * @param array $fieldset
     * @return array
     */
    protected function getImportedFieldsetHandles($fieldset)
    {
        $flattened = Arr::dot($fieldset);

        return collect($flattened)
            ->filter(function ($value, $key) {
                return preg_match('/fields.*type$/', $key) && $value === 'partial';
            })
            ->map(function ($value, $key) {
                return preg_replace('/(.*)\.type$/', "$1.fieldset", $key);
            })
            ->map(function ($fieldsetKey) use ($flattened) {
                return $flattened[$fieldsetKey];
            });
    }
}
