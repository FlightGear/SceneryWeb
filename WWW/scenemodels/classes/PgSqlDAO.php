<?php
require_once 'PGDatabase.php';

abstract class PgSqlDAO {
    protected $database;
    
    public function __construct(PGDatabase $database) {
        $this->database = $database;
        date_default_timezone_set("America/Los_Angeles");
    }
}
