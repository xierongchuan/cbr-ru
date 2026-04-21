<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckConfigTest extends TestCase
{
    public function test_db_config()
    {
        fwrite(STDOUT, "\nDB_CONNECTION: ".config('database.default')."\n");
        fwrite(STDOUT, 'DB_DATABASE: '.config('database.connections.'.config('database.default').'.database')."\n");
        $this->assertTrue(true);
    }
}
