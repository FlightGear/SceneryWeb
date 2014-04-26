<?php

interface IObjectDAO {

    public function addObject($object);

    public function updateObject($object);
    
    public function getObject($objectId);
    
    public function getObjects($pagesize, $offset, $criteria=null);
    
    public function getObjectsByModel($modelId);
    
    public function countObjects();
    
    public function countObjectsByModel($modelId);

}

?>
