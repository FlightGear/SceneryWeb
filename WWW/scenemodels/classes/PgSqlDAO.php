<?php
require_once 'PGDatabase.php';

abstract class PgSqlDAO {
    protected $database;
    
    public function __construct(PGDatabase $database) {
        $this->database = $database;
        date_default_timezone_set("America/Los_Angeles");
    }
    
    protected function generateWhereClauseCriteria($criteria) {
        // Generating WHERE clause from criteria
        $whereClause = "";
        if (isset($criteria) && count($criteria)>0) {
            $whereClause = "";
            $and = '';
            foreach ($criteria as $criterion) {
                $whereClause .= $and . $criterion->getVarName() 
                                . $criterion->getOperation()
                                . pg_escape_string($criterion->getValue());
                $and = ' AND ';
            }
        }
        
        return $whereClause;
    }
}
