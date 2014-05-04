<?php

require_once "Database.php";

class PGDatabase implements Database {
    private $connection;

    public function __construct($dbname, $dbhost, $dbuser, $dbpass) {
        $this->connection = pg_pconnect('dbname='.$dbname.' host='.$dbhost.
                         ' user='.$dbuser.' password='.$dbpass.' sslmode=disable');
    }
    
    public function __destruct() {
        pg_close($this->connection);
    }
    
    public function query($query) {
        return pg_query($this->connection, $query);
    }
}

?>