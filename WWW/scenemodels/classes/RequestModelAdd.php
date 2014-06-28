<?php

require_once 'Request.php';

class RequestModelAdd extends Request {
    private $newModel;
    private $newObject;
    
    public function getNewModel() {
        return $this->newModel;
    }
    
    public function setNewModel($newModel) {
        $this->newModel = $newModel;
    }
    
    public function getNewObject() {
        return $this->newObject;
    }
    
    public function setNewObject($newObject) {
        $this->newObject = $newObject;
    }
}

?>
