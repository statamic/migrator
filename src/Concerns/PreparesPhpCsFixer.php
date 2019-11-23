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
        if (! File::exists($batFile = getcwd().'/vendor/bin/php-cs-fixer.bat')) {
            File::copy(__DIR__.'/../../bin/php-cs-fixer.bat', $batFile);
        }
    }
}
