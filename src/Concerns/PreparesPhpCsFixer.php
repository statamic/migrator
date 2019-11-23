<?php

namespace Statamic\Migrator\Concerns;

use Illuminate\Support\Facades\File;

trait PreparesPhpCsFixer
{
    /**
     * Prepare vendor/bin/php-cs-fixer to be cross-platform.
     */
    protected function preparePhpCsFixer()
    {
        tap(base_path('vendor/bin/php-cs-fixer.bat'), function ($batFile) {
            if (! File::exists($batFile)) {
                File::copy(__DIR__.'/../../bin/php-cs-fixer.bat', $batFile);
            }
        });
    }
}
