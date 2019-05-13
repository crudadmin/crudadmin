<?php

namespace Gogol\Admin\Tests\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait DropDatabase
{
    /*
     * Drop all tables in database
     */
    public function dropDatabase(){
        $tables = DB::select('SHOW TABLES');

        foreach($tables as $table)
        {
            $table = array_values((array)$table)[0];
            Schema::drop($table);
        }
    }
}