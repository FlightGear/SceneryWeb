<?php

interface IModelDAO {

    public function addModel($model);

    public function updateModel($model);
    
    public function getModel($modelId);
    
    public function countTotalModels();
    
    public function countModelsNoThumb();
    
    public function addModelMetadata($modelMetadata);

    public function updateModelMetadata($modelMetadata);
    
    public function getModelMetadata($modelId);
    
    public function getModelMetadatas($offset, $pagesize);

    public function getModelMetadatasByAuthor($authorId);
    
    public function getModelMetadatasByGroup($modelId, $offset, $pagesize);
    
    public function getModelMetadatasNoThumb($offset, $pagesize);
    
    public function getModelsGroups();
    
    public function getModelFiles($modelId);
}

?>
