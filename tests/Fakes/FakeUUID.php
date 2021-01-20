<?php

namespace Tests\Fakes;

use Statamic\Migrator\UUID;

class FakeUUID
{
    public function generate($uuid = null)
    {
        return $uuid ?? UUID::generate();
    }
}
