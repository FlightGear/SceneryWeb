<?php

require_once 'Database.php';
require_once 'PGDatabase.php';
require_once 'ObjectDAO.php';
require_once 'ModelDAO.php';
require_once 'AuthorDAO.php';
require_once 'NewsPostDAO.php';
require_once 'RequestDAO.php';

class DAOFactory {
    private static $instance;
    private $db_readonly;
    private $db_readwrite;
 

    private function __construct() {
        include "/home/ojacq/.scenemodels";
        $this->db_readonly = new PGDatabase($database, $host, $ro_user, $ro_pass);
        $this->db_readwrite = new PGDatabase($database, $host, $rw_user, $rw_pass);
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
    
    public function getRequestDaoRO() {
        return new RequestDAO($this->db_readonly, $this->getObjectDaoRO(),
                $this->getModelDaoRO(), $this->getAuthorDaoRO());
    }
    
    public function getRequestDaoRW() {
        return new RequestDAO($this->db_readwrite, $this->getObjectDaoRW(),
                $this->getModelDaoRW(), $this->getAuthorDaoRW());
    }
}

?>