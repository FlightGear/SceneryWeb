<?php

require_once 'Database.php';
require_once 'PGDatabase.php';
require_once 'ObjectDAO.php';
require_once 'ModelDAO.php';
require_once 'AuthorDAO.php';
require_once 'NewsPostDAO.php';

class DAOFactory {
    private static $instance;
    private $db_readonly;
    private $db_readwrite;
 

    private function __construct() {
        //include "/home/ojacq/.scenemodels";
        $dbname = "landcover";
        $dbhost = "localhost";
        $dbuser = "webuser";
        $dbpass = "";
        $this->db_readonly = new PGDatabase($dbname, $dbhost, $dbuser, $dbpass);
        
        //$db_readwrite = new PGDatabase();
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new DAOFactory();
        }
        
        return self::$instance;
    }
    
    public function getModelDaoRO() {
        return new ModelDAO($this->db_readonly);
    }
    
    public function getModelDaoRW() {
        return new ModelDAO($this->db_readwrite);
    }
    
    public function getObjectDaoRO() {
        return new ObjectDAO($this->db_readonly);
    }
    
    public function getObjectDaoRW() {
        return new ObjectDAO($this->db_readwrite);
    }
    
    public function getAuthorDaoRO() {
        return new AuthorDAO($this->db_readonly);
    }
    
    public function getAuthorDaoRW() {
        return new AuthorDAO($this->db_readwrite);
    }
    
     public function getNewsPostDaoRO() {
        return new NewsPostDAO($this->db_readonly);
    }
    
    public function getNewsPostDaoRW() {
        return new NewsPostDAO($this->db_readwrite);
    }
}

?>
