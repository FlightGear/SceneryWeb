<?php

require_once 'Request.php';

class RequestModelUpdate extends Request {
    private $newModel;
    private $oldModel;
    
    public function getNewModel() {
        return $this->newModel;
    }
    
    public function setNewModel($newModel) {
        $this->newModel = $newModel;
    }
    
    public function getOldModel() {
        return $this->oldModel;
    }
    
    public function setOldModel($oldModel) {
        $this->oldModel = $oldModel;
    }
}

?>
