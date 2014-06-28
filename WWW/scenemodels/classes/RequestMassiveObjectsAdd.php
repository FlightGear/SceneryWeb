<?php

require_once 'Request.php';

class RequestMassiveObjectsAdd extends Request {
    private $newObjects;
    
    public function getNewObjects() {
        return $this->newObjects;
    }
    
    public function setNewObjects($newObjects) {
        $this->newObjects = $newObjects;
    }

}

?>
