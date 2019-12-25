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

            $ip = trim(Zttp::timeout(1.5)->get('https://icanhazip.com')->body());
            $ipValidator = Validator::make(['ip' => $ip], ['ip' => 'required|ip']);

            if ($ipValidator->passes()) {
                Zttp::timeout(1.5)->post('https://outpost.statamic.com/v3/migrator-stats', array_merge([
                    'app' => md5($ip . base_path())
                ], $stats));
            }
        } catch (Exception $exception) {
            //
        }
    }
}
