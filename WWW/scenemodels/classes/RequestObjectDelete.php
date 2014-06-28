<?php

require_once 'Request.php';

class RequestObjectDelete extends Request {
    private $objToDelete;
    
    public function getObjectToDelete() {
        return $this->objToDelete;
    }
    
    public function setObjectToDelete($objToDelete) {
        $this->objToDelete = $objToDelete;
    }
}

?>
