<?php

require_once 'Request.php';

class RequestObjectAdd extends Request {
    private $newObject;
    
    public function getNewObject() {
        return $this->newObject;
    }
    
    public function setNewObject($newObject) {
        $this->newObject = $newObject;
    }
}

?>
