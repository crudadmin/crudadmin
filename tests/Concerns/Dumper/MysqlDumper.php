<?php

namespace Admin\Tests\Concerns\Dumper;

use Config;

class MySqlDumper
{
    static $dumped = false;

    protected $console;
    protected $database;
    protected $user;
    protected $password;
    protected $host;
    protected $port;

    public function __construct()
    {
        $db = config('database.connections.mysql');

        $this->database = $db['database'];
        $this->user = $db['username'];
        $this->password = $db['password'];
        $this->host = $db['host'];
        $this->port = $db['port'];
    }

    private function getDestinationPath()
    {
        return storage_path('app/db_dump.sql');
    }

    public function dump()
    {
        $command = "mysqldump --user=".$this->user. " --password=".$this->password." --host=".$this->host." ".$this->database." > ".$this->getDestinationPath()." 2> /dev/null";

        shell_exec($command);

        self::$dumped = true;
    }

    public function cacheDatabaseAndRestore($installCallback)
    {
        if ( !file_exists($this->getDestinationPath()) ) {
            $installCallback();

            $this->dump();
        }

        $command = "mysql --user=".$this->user. " --password=".$this->password." --host=".$this->host." ".$this->database." < ".$this->getDestinationPath()." 2> /dev/null";

        shell_exec($command);
    }
}