<?php

namespace Statamic\Migrator\Concerns;

use Exception;
use Statamic\Support\Str;
use Zttp\Zttp;

trait SubmitsStats
{
    /**
     * Attempt submitting anonymous stats.
     *
     * @param array $stats
     */
    protected function attemptSubmitStats($stats)
    {
        if (Str::contains(base_path(), 'orchestra/testbench-core')) {
            return;
        }

        try {
            $stats['command'] = str_replace('statamic:', '', $stats['command']);

            Zttp::timeout(3)->post('https://outpost.statamic.com/v3/migrator-stats', array_merge([
                'app' => md5(base_path()),
            ], $stats));
        } catch (Exception $exception) {
            //
        }
    }
}
