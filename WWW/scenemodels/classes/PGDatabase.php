<?php

class PGDatabase implements Database {
    private $connection;

    public function __construct($dbname, $dbhost, $dbuser, $dbpass) {
        $this->connection = pg_connect('dbname='.$dbname.' host='.$dbhost.
                         ' user='.$dbuser.' password='.$dbpass.' sslmode=disable');
    }
    
    public function query($query) {
        return pg_query($this->connection, $query);
    }
}

?>