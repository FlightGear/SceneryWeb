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

    private function __construct() {
    }
    
    private function getDBReadOnly() {
        include "/home/ojacq/.scenemodels";
        return new PGDatabase($database, $host, $ro_user, $ro_pass);
    }
    
    private function getDBReadWrite() {
        include "/home/ojacq/.scenemodels";
        return new PGDatabase($database, $host, $rw_user, $rw_pass);
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new DAOFactory();
        }
        
        return self::$instance;
    }
    
    public function getModelDaoRO() {
        return new ModelDAO($this->getDBReadOnly());
    }
    
    public function getModelDaoRW() {
        return new ModelDAO($this->getDBReadWrite());
    }
    
    public function getObjectDaoRO() {
        return new ObjectDAO($this->getDBReadOnly());
    }
    
    public function getObjectDaoRW() {
        return new ObjectDAO($this->getDBReadWrite());
    }
    
    public function getAuthorDaoRO() {
        return new AuthorDAO($this->getDBReadOnly());
    }
    
    public function getAuthorDaoRW() {
        return new AuthorDAO($this->getDBReadWrite());
    }
    
    public function getNewsPostDaoRO() {
        return new NewsPostDAO($this->getDBReadOnly());
    }
    
    public function getNewsPostDaoRW() {
        return new NewsPostDAO($this->getDBReadWrite());
    }
    
    public function getRequestDaoRO() {
        return new RequestDAO($this->getDBReadOnly(), $this->getObjectDaoRO(),
                $this->getModelDaoRO(), $this->getAuthorDaoRO());
    }
    
    public function getRequestDaoRW() {
        return new RequestDAO($this->getDBReadWrite(), $this->getObjectDaoRW(),
                $this->getModelDaoRW(), $this->getAuthorDaoRW());
    }
}

?>