<?php

require_once 'Request.php';

class RequestObjectUpdate extends Request {
    private $newObject;
    private $oldObject;
    
    public function getNewObject() {
        return $this->newObject;
    }
    
    public function setNewObject($newObject) {
        $this->newObject = $newObject;
    }
    
    public function getOldObject() {
        return $this->oldObject;
    }
    
    public function setOldObject($oldObject) {
        $this->oldObject = $oldObject;
    }
}

?>