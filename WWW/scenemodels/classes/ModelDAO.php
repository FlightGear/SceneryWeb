<?php
require_once 'PgSqlDAO.php';
require_once 'IModelDAO.php';
require_once 'Model.php';
require_once 'Author.php';
require_once 'ModelMetadata.php';
require_once 'ModelGroup.php';
require_once 'ModelFilesTar.php';
require_once 'Criterion.php';

/**
 * Model Data Access Object implementation for PostgreSQL
 *
 * Database layer to access models from PostgreSQL database
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */

class ModelDAO extends PgSqlDAO implements IModelDAO {    
    public function addModel($model) {
        //TODO
    }

    public function updateModel($model) {
        //TODO
    }
    
    public function getModel($modelId) {
        $result = $this->database->query("SELECT * FROM fgs_models, fgs_authors, fgs_modelgroups ".
                                         "WHERE mo_id = $modelId AND au_id = mo_author AND mg_id = mo_shared");
        $row = pg_fetch_assoc($result);
        
        return $this->getModelFromRow($row);
    }
    
    public function addModelMetadata($modelMetadata) {
        //TODO
    }

    public function updateModelMetadata($modelMetadata){
        //TODO
    }
    
    public function getModelMetadata($modelId){
        $result = $this->database->query("SELECT mo_id, mo_path, mo_name, mo_notes, mo_modified, ".
                                         "mg_id, mg_name, mg_path, au_id, au_name, au_email, au_notes ".
                                         "FROM fgs_models, fgs_authors, fgs_modelgroups ".
                                         "WHERE mo_id = $modelId AND au_id = mo_author AND mg_id = mo_shared");
        $row = pg_fetch_assoc($result);
        
        return $this->getModelMetadataFromRow($row);
    }
    
    public function countTotalModels() {
        $result = $this->database->query("SELECT COUNT(*) AS number " .
                                        "FROM fgs_models;");
        $row = pg_fetch_assoc($result);
        
        return $row["number"];
    }
    
    public function countModelsNoThumb() {
        $result = $this->database->query("SELECT COUNT(*) AS number " .
                                         "FROM fgs_models " .
                                         "WHERE mo_thumbfile IS NULL;");
                                         
        $row = pg_fetch_assoc($result);
        
        return $row["number"];
    }
    
    private function getModelFromRow($row) {
        $modelMetadata = $this->getModelMetadataFromRow($row);
        
        $model = new Model();
        $model->setMetadata($modelMetadata);
        $model->setModelFiles(new ModelFilesTar(base64_decode($row["mo_modelfile"])));
        $model->setThumbnail(base64_decode($row["mo_thumbfile"]));
        
        return $model;
    }
    
    private function getModelMetadataFromRow($row) {
        $author = new Author();
        $author->setId($row["au_id"]);
        $author->setName($row["au_name"]);
        $author->setEmail($row["au_email"]);
        $author->setDescription($row["au_notes"]);

        $modelGroup = $this->getModelGroupFromRow($row);
        
        $modelMetadata = new ModelMetadata();
        $modelMetadata->setId($row["mo_id"]);
        $modelMetadata->setAuthor($author);
        $modelMetadata->setFilename($row["mo_path"]);
        $modelMetadata->setName($row["mo_name"]);
        $modelMetadata->setDescription($row["mo_notes"]);
        $modelMetadata->setModelGroup($modelGroup);
        $modelMetadata->setLastUpdated(new DateTime($row['mo_modified']));

        return $modelMetadata;
    }
    
    private function getModelGroupFromRow($row) {
        $modelGroup = new ModelGroup();
        $modelGroup->setId($row["mg_id"]);
        $modelGroup->setName($row["mg_name"]);
        $modelGroup->setPath($row["mg_path"]);
        
        return $modelGroup;
    }
    
    
    public function getModelMetadatas($offset, $pagesize, $criteria=null, $orderby="mo_modified", $order="ASC") {
        // Generating WHERE clause from criteria
        $whereClause = $this->generateWhereClauseCriteria($criteria);
        if ($whereClause != "") {
            $whereClause .= " AND"; 
        }
        
        $result = $this->database->query("SELECT mo_id, mo_path, mo_name, mo_notes, mo_modified, ".
                                         "mg_id, mg_name, mg_path, au_id, au_name, au_email, au_notes ".
                                         "FROM fgs_models, fgs_authors, fgs_modelgroups ".
                                         "WHERE $whereClause au_id = mo_author AND mg_id = mo_shared ".
                                         "ORDER BY ".$orderby." ".$order." LIMIT ".$pagesize." OFFSET ".$offset.";");
        
        $resultArray = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $resultArray[] = $this->getModelMetadataFromRow($row);
        }
        
        return $resultArray;
    }
    
    public function getModelMetadatasNoThumb($offset, $pagesize) {
        $criteria = array();
        $criteria[] = new Criterion("mo_thumbfile", Criterion::OPERATION_IS, "NULL");
        return $this->getModelMetadatas($offset, $pagesize, $criteria);
    }
    
    public function getModelMetadatasByAuthor($authorId) {
        $criteria = array();
        $criteria[] = new Criterion("mo_author", Criterion::OPERATION_EQ, $authorId);
        return $this->getModelMetadatas(0, "ALL", $criteria);
    }
    
    public function getModelMetadatasByGroup($modelGroupId, $offset, $pagesize, $orderby="mo_modified", $order="ASC") {
        $criteria = array();
        $criteria[] = new Criterion("mo_shared", Criterion::OPERATION_EQ, $modelGroupId);
        return $this->getModelMetadatas($offset, $pagesize, $criteria, $orderby, $order);
    }
    
    public function getPaths() {
        $result = $this->database->query("SELECT mo_id, mo_path ".
                                         "FROM fgs_models ".
                                         "ORDER BY mo_path ASC;");
        
        $resultArray = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $resultArray[$row["mo_id"]] = $row["mo_path"];
        }
        
        return $resultArray;
    }
    
    public function getModelsGroup($groupId) {
        $result = $this->database->query("SELECT mg_id, mg_name, mg_path ".
                                         "FROM fgs_modelgroups ".
                                         "WHERE mg_id = $groupId;");
                           
        $row = pg_fetch_assoc($result);
        return $this->getModelGroupFromRow($row);
    }
    
    public function getModelsGroups() {
        $result = $this->database->query("SELECT mg_id, mg_name, mg_path ".
                                         "FROM fgs_modelgroups ".
                                         "ORDER BY mg_name;");
        
        $resultArray = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $resultArray[] = $this->getModelGroupFromRow($row);
        }
        
        return $resultArray;
    }
    
    public function getModelFiles($modelId) {
        $result = $this->database->query("SELECT mo_modelfile FROM fgs_models WHERE mo_id=$modelId;");
        $row = pg_fetch_assoc($result);
        
        return new ModelFilesTar(base64_decode($row["mo_modelfile"]));
    }

    public function getThumbnail($modelId) {
        $result = $this->database->query("SELECT mo_thumbfile FROM fgs_models WHERE mo_id=".$modelId.";");
        $row = pg_fetch_assoc($result);
        
        return base64_decode($row["mo_thumbfile"]);
    }
}

?>