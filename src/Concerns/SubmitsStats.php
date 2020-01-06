<?php

namespace Statamic\Migrator\Concerns;

use Exception;
use Zttp\Zttp;
use Illuminate\Support\Facades\Validator;

trait SubmitsStats
{
    /**
     * Attempt submitting anonymous stats.
     *
     * @param array $stats
     */
    protected function attemptSubmitStats($stats)
    {
        try {
            $stats['command'] = str_replace('statamic:', '', $stats['command']);

            Zttp::timeout(1.5)->post('https://outpost.statamic.com/v3/migrator-stats', array_merge([
                'app' => md5(base_path())
            ], $stats));
        } catch (Exception $exception) {
            //
        }
    }
}
