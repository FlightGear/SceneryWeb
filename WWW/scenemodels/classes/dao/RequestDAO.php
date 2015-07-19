<?php
namespace dao;

/*
 * Copyright (C) 2014 Flightgear Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


/**
 * Request DAO
 *
 * @author Julien Nguyen <julien.nguyen3@gmail.com>
 */
class RequestDAO extends PgSqlDAO implements IRequestDAO {
    private $objectDao;
    private $modelDao;
    private $authorDao;
    
    public function __construct(PGDatabase $database, ObjectDAO $objectDao,
            ModelDAO $modelDAO, AuthorDAO $authorDAO) {
        parent::__construct($database);
        $this->objectDao = $objectDao;
        $this->modelDao = $modelDAO;
        $this->authorDao = $authorDAO;
    }
    
    public function getRequest($sig) {
        $result = $this->database->query("SELECT spr_id, spr_hash, spr_base64_sqlz ".
                                         "FROM fgs_position_requests ".
                                         "WHERE spr_hash = '". $sig ."';");
        $row = pg_fetch_assoc($result);
        
        if (!$row) {
            throw new RequestNotFoundException('No request with sig '. $sig. ' was found!');
        }
        
        return $this->getRequestFromRow($row);
    }
    
    public function saveRequest($request) {
        $reqStr = $this->serializeRequest($request);
        
        $zippedQuery = gzcompress($reqStr,8);
        $encodedReqStr = base64_encode($zippedQuery);
        
        $shaToCompute = "<".microtime()."><".$_SERVER["REMOTE_ADDR"]."><".$encodedReqStr.">";
        $sig = hash('sha256', $shaToCompute);
        
        $query = "INSERT INTO fgs_position_requests (spr_id, spr_hash, spr_base64_sqlz) VALUES (DEFAULT, '".$sig."', '".$encodedReqStr."') RETURNING spr_id;";
        
        $result = $this->database->query($query);
        
        if (!$result) {
            throw new \Exception("Adding object failed!");
        }
        
        $returnRow = pg_fetch_row($result);
        $request->setId($returnRow[0]);
        $request->setSig($sig);
        return $request;
    }
    
    private function serializeRequest($request) {
        switch (get_class($request)) {
        case "model\RequestObjectUpdate":
            $reqStrContent = $this->serializeRequestObjectUpdate($request);
            break;
        
        case "model\RequestObjectDelete":
            $reqStrContent = $this->serializeRequestObjectDelete($request);
            break;
        
        case "model\RequestMassiveObjectsAdd":
            $reqStrContent = $this->serializeRequestMassiveObjectsAdd($request);
            break;
        
        case "model\RequestModelAdd":
            $reqStrContent = $this->serializeRequestModelAdd($request);
            break;
        
        case "model\RequestModelUpdate":
            $reqStrContent = $this->serializeRequestModelUpdate($request);
            break;
        
        default:
            throw new \Exception("Not a request!");
        }
        
        $reqArray = array("email"=>$request->getContributorEmail(),
                     "comment"=>$request->getComment(),
                     "content"=>$reqStrContent);
        
        return json_encode($reqArray);
    }
    
    private function serializeObject(\model\Object $object) {
        $objPos = $object->getPosition();
        $offset = $object->getElevationOffset();
        
        return "OBJECT_ADD||" .
               $object->getDescription(). "||" . // ob_text
               $objPos->getLongitude(). "||" . // longitude
               $objPos->getLatitude(). "||" . // latitude
               (empty($offset)?"NULL":$offset). "||" . // elevation offset
               $object->getOrientation(). "||" . // orientation
               $object->getCountry()->getCode(). "||" . //country
               $object->getModelId(); // model id
    }
    
    private function serializeRequestObjectUpdate($request) {
        $newObj = $request->getNewObject();
        $newObjPos = $newObj->getPosition();
        $offset = $newObj->getElevationOffset();
        
        return "OBJECT_UPDATE||" .
               $newObj->getDescription(). "||" . // ob_text
               $newObjPos->getLongitude(). "||" . // longitude
               $newObjPos->getLatitude(). "||" . // latitude
               (empty($offset)?"NULL":$offset). "||" . // elevation offset
               $newObj->getOrientation(). "||" . // orientation
               $newObj->getCountry()->getCode(). "||" . //country
               $newObj->getModelId(). "||" . // model id
               $newObj->getId(); // ob_id
    }
    
    private function serializeRequestObjectDelete($request) {
        $objToDel = $request->getObjectToDelete();
        
        return "OBJECT_DELETE||".$objToDel->getId();
    }
    
    private function serializeRequestMassiveObjectsAdd($request) {
        $newObjects = $request->getNewObjects();
        
        // Proceed on with the request generation
        $reqArray = array();
        
        // For each line, add the data content to the request
        foreach ($newObjects as $newObj) {
            $reqArray[] = $this->serializeObject($newObj);
        }
        
        return json_encode($reqArray);
    }
    
    private function serializeRequestModelAdd($request) {
        $newModel = $request->getNewModel();
        $newModelMD = $newModel->getMetadata();
        $newObject = $request->getNewObject();
        
        $moQuery  = "MODEL_ADD||";
        $moQuery .= $newModelMD->getFilename()."||";        // mo_path
        $moQuery .= $newModelMD->getAuthor()->getId()."||"; // mo_author
        $moQuery .= $newModelMD->getName()."||";            // mo_name
        $moQuery .= $newModelMD->getDescription()."||";     // mo_notes
        $moQuery .= $newModel->getThumbnail()."||";         // mo_thumbfile
        $moQuery .= $newModel->getModelFiles()."||";        // mo_modelfile
        $moQuery .= $newModelMD->getModelsGroup()->getId(); // mo_shared

        // Serialize object
        $obQuery = $this->serializeObject($newObject);

        return $moQuery."|||".$obQuery;
    }
    
    private function serializeRequestModelUpdate($request) {
        $newModel = $request->getNewModel();
        $newModelMD = $newModel->getMetadata();
        
        return "MODEL_UPDATE||".
               $newModelMD->getFilename()."||".             // mo_path
               $newModelMD->getAuthor()->getId()."||".      // mo_author
               $newModelMD->getName()."||".                 // mo_name
               $newModelMD->getDescription()."||".          // mo_notes
               $newModel->getThumbnail()."||".              // mo_thumbfile
               $newModel->getModelFiles()."||".             // mo_modelfile
               $newModelMD->getModelsGroup()->getId()."||". // mo_shared
               $newModelMD->getId();                        // mo_id
    }
    
    public function deleteRequest($sig) {
        // Checking the presence of sig into the database
        $result = $this->database->query("SELECT 1 FROM fgs_position_requests WHERE spr_hash = '". $sig ."';");
        $row = pg_fetch_assoc($result);
        // If not ok...
        if (!$row) {
            throw new RequestNotFoundException('No request with sig '. $sig. ' was found!');
        }
        
        // Delete the entry from the pending query table.
        $resultdel = $this->database->query("DELETE FROM fgs_position_requests WHERE spr_hash = '". $sig ."';");

        return $resultdel != FALSE;
    }
    
    public function getPendingRequests() {
        $result = $this->database->query("SELECT spr_id, spr_hash, spr_base64_sqlz ".
                                         "FROM fgs_position_requests ".
                                         "ORDER BY spr_id ASC;");
        $resultArray = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            try {
                $resultArray[] = $this->getRequestFromRow($row);
            } catch (Exception $ex) {
                error_log("Error with request ".$row['spr_id'].": ". $ex->getMessage());
            }
        }
        
        return $resultArray;
    }
    
    private function getRequestFromRow($requestRow) {
        // Decoding in Base64. Dezipping the Base64'd request.
        $requestJson = gzuncompress(base64_decode($requestRow["spr_base64_sqlz"]));
        
        $requestArray = json_decode($requestJson, true);
        $requestQuery = $requestArray["content"];
        
        // Delete object request
        if (strpos($requestQuery,"OBJECT_DELETE") === 0) {
            $request = $this->getRequestObjectDeleteFromRow($requestQuery);
        }
        
        // Update object request
        if (strpos($requestQuery,"OBJECT_UPDATE") === 0) {
            $request = $this->getRequestObjectUpdateFromRow($requestQuery);
        }

        // Add object request
        if (strpos($requestQuery,"OBJECT_ADD") === 0) {
            $request = $this->getRequestMassiveObjectsAddFromRow('["'.$requestQuery.'"]');
        }
        
        // Add massive objects request
        if (json_decode($requestQuery) != null) {
            $request = $this->getRequestMassiveObjectsAddFromRow($requestQuery);
        }
        
        // Add model request
        if (strpos($requestQuery,"MODEL_ADD") === 0) {
            $request = $this->getRequestModelAddFromRow($requestQuery);
        }
        
        // Update model request
        if (strpos($requestQuery,"MODEL_UPDATE") === 0) {
            $request = $this->getRequestModelUpdateFromRow($requestQuery);
        }
        
        if (isset($request)) {
            $request->setId($requestRow["spr_id"]);
            $request->setSig($requestRow["spr_hash"]);
            $request->setComment($requestArray["comment"]);
            $request->setContributorEmail($requestArray["email"]);
            
            return $request;
        } else {
            throw new \Exception("Error reading request: ". $requestQuery);
        }
    }
    
    private function getRequestModelAddFromRow($requestQuery) {
        $queryModel = substr($requestQuery, 0, strpos($requestQuery, "|||OBJECT_ADD"));
        $queryObj = strstr($requestQuery, "OBJECT_ADD");

        // Retrieve MODEL data from query
        // MODEL_ADD||mo_path||mo_author||mo_name||mo_notes||mo_thumbfile||mo_modelfile||mo_shared
        $modelArr = explode("||", $queryModel);
        $modelFactory = new \ModelFactory($this->modelDao, $this->authorDao);
        $modelMD = $modelFactory->createModelMetadata(-1, $modelArr[2], $modelArr[1], $modelArr[3], $modelArr[4], $modelArr[7]);
        $newModel = new \model\Model();
        $newModel->setMetadata($modelMD);
        $newModel->setModelFiles(new \ModelFilesTar(base64_decode($modelArr[6])));
        $newModel->setThumbnail(base64_decode($modelArr[5]));

        // Retrieve OBJECT data from query
        $newObject = $this->getObjectFromSerialized($queryObj);
        
        $requestModelAdd = new \model\RequestModelAdd();
        $requestModelAdd->setNewModel($newModel);
        $requestModelAdd->setNewObject($newObject);
        
        return $requestModelAdd;
    }
    
    private function getRequestModelUpdateFromRow($requestQuery) {
        // Retrieve data from query
        // MODEL_UPDATE||path||author||name||notes||thumbfile||modelfile||shared||modelid
        $modelArr = explode("||", $requestQuery);

        $modelFactory = new \ModelFactory($this->modelDao, $this->authorDao);
        $modelMD = $modelFactory->createModelMetadata($modelArr[8],
                $modelArr[2], $modelArr[1], $modelArr[3],
                $modelArr[4], $modelArr[7]);
        
        $newModel = new \model\Model();
        $newModel->setMetadata($modelMD);
        $newModel->setModelFiles(new \ModelFilesTar(base64_decode($modelArr[6])));
        $newModel->setThumbnail(base64_decode($modelArr[5]));

        // Retrieve old model
        $oldModel = $this->modelDao->getModel($modelMD->getId());
        
        $requestModelUpd = new \model\RequestModelUpdate();
        $requestModelUpd->setNewModel($newModel);
        $requestModelUpd->setOldModel($oldModel);
        
        return $requestModelUpd;
    }
    
    private function getObjectFromSerialized($objectSerialized) {
        // OBJECT_ADD||ob_text||longitude||latitude||elevation offset||orientation||country||model id
        $objectArr = explode("||", $objectSerialized);
        
        $objectFactory = new \ObjectFactory($this->objectDao);
        
        return $objectFactory->createObject(-1, $objectArr[7],
               $objectArr[2], $objectArr[3], $objectArr[6], 
               $objectArr[4], $objectArr[5], 1, $objectArr[1]);
    }
    
    private function getRequestMassiveObjectsAddFromRow($requestQuery) {
        // Separating the data
        $objRequests = json_decode($requestQuery);
        $newObjects = array();
        
        foreach ($objRequests as $objRequest) {
            $newObject = $this->getObjectFromSerialized($objRequest);
            $newObjects[] = $newObject;
        }
        
        $requestMassObjAdd = new \model\RequestMassiveObjectsAdd();
        $requestMassObjAdd->setNewObjects($newObjects);
        
        return $requestMassObjAdd;
    }
    
    private function getRequestObjectUpdateFromRow($requestQuery) {
        // OBJECT_UPDATE||ob_text||longitude||latitude||elevation offset||orientation||country||model id||ob id
        $objectArr = explode("||", $requestQuery);
        
        $objectFactory = new \ObjectFactory($this->objectDao);

        $newObject = $objectFactory->createObject($objectArr[8], $objectArr[7],
               $objectArr[2], $objectArr[3], $objectArr[6], 
               $objectArr[4], $objectArr[5], 1, $objectArr[1]);

        $requestObjUp = new \model\RequestObjectUpdate();
        $requestObjUp->setContributorEmail("");
        $requestObjUp->setComment("");
        $requestObjUp->setNewObject($newObject);
        $requestObjUp->setOldObject($this->objectDao->getObject($objectArr[8]));
        
        return $requestObjUp;
    }
    
    private function getRequestObjectDeleteFromRow($requestQuery) {
        // OBJECT_DELETE||ob_id
        $objectArr = explode("||", $requestQuery);
        $objectToDel = $this->objectDao->getObject($objectArr[1]);

        $requestObjDel = new \model\RequestObjectDelete();
        
        // Not available with actual DAO
        $requestObjDel->setContributorEmail("");
        $requestObjDel->setComment("");
        
        $requestObjDel->setObjectToDelete($objectToDel);

        return $requestObjDel;
    }
}
?>