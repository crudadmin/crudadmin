<?php

namespace Gogol\Admin\Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait DropDatabase
{
    /*
     * Drop all tables in database
     */
    public function dropDatabase(){
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        $tables = DB::select('SHOW TABLES');

        foreach($tables as $table)
        {
            $table = array_values((array)$table)[0];
            Schema::drop($table);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}