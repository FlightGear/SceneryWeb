<?php

interface IModelFiles {

    public function getPackage();

    public function getACFile();
    
    public function getXMLFile();
    
    public function getTexturesNames();
    
    public function getTexture($filename);
}

?>
