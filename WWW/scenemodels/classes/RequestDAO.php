<?php

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

require_once 'IRequestDAO.php';
require_once 'Request.php';
require_once 'RequestMassiveObjectsAdd.php';
require_once 'RequestModelAdd.php';
require_once 'RequestModelUpdate.php';
require_once 'RequestObjectAdd.php';
require_once 'RequestObjectDelete.php';
require_once 'RequestObjectUpdate.php';
require_once 'ObjectDAO.php';
require_once 'ObjectFactory.php';
require_once 'ModelFactory.php';
require_once 'ModelFilesTar.php';

require_once 'RequestNotFoundException.php';

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
        $result = $this->database->query("SELECT spr_hash, spr_base64_sqlz ".
                                         "FROM fgs_position_requests ".
                                         "WHERE spr_hash = '". $sig ."';");
        
        $row = pg_fetch_assoc($result);
        
        if (!$row) {
            throw new RequestNotFoundException('No request with sig '. $sig. ' was found!');
        }
        
        return $this->getRequestFromRow($row);
    }
    
    public function saveRequest($request) {
        
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
        $result = $this->database->query("SELECT spr_hash, spr_base64_sqlz ".
                                         "FROM fgs_position_requests ".
                                         "ORDER BY spr_id ASC;");
        $resultArray = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $resultArray[] = $this->getRequestFromRow($row);
        }
        
        return $resultArray;
    }
    
    private function getRequestFromRow($requestRow) {
        // Decoding in Base64. Dezipping the Base64'd request.
        $requestQuery = gzuncompress(base64_decode($requestRow["spr_base64_sqlz"]));
        
        // Delete object request
        if (substr_count($requestQuery,"DELETE FROM fgs_objects") == 1) {
            $request = $this->getRequestObjectDeleteFromRow($requestQuery);
        }
        
        // Update object request
        if (substr_count($requestQuery,"UPDATE fgs_objects") == 1) {
            $request = $this->getRequestObjectUpdateFromRow($requestQuery);
        }
        
        // Add object(s) request
        if (substr_count($requestQuery,"INSERT INTO fgs_objects") == 1 && substr_count($requestQuery,"Thisisthevalueformo_id") == 0) {
            if (substr_count($requestQuery,"INSERT INTO fgs_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_country, ob_model, ob_group)") == 1) {
                $request = $this->getRequestObjectAddFromRow($requestQuery);
            }
            // Else, it is a mass insertion
            else {
                $request = $this->getRequestMassiveObjectsAddFromRow($requestQuery);
            }
        }
        
        // Add model request
        if (substr_count($requestQuery,"INSERT INTO fgs_models") == 1) {
            $request = $this->getRequestModelAddFromRow($requestQuery);
        }
        
        // Update model request
        if (substr_count($requestQuery,"UPDATE fgs_models") == 1) {
            $request = $this->getRequestModelUpdateFromRow($requestQuery);
        }
        
        $request->setSig($requestRow["spr_hash"]);
        
        return $request;
    }
    
    private function getRequestModelAddFromRow($requestQuery) {
        $queryModel = substr($requestQuery, 0, strpos($requestQuery, "INSERT INTO fgs_objects"));
        $queryObj = strstr($requestQuery, "INSERT INTO fgs_objects");

        // Retrieve MODEL data from query
        $pattern = "/INSERT INTO fgs_models \(mo_id, mo_path, mo_author, mo_name, mo_notes, mo_thumbfile, mo_modelfile, mo_shared\) VALUES \(DEFAULT, '(?P<path>[a-zA-Z0-9_.-]+)', (?P<author>[0-9]+), '(?P<name>[a-zA-Z0-9,;:?@ !_.-]+)', '(?P<notes>[a-zA-Z0-9 ,!_.-]*)', '(?P<thumbfile>[a-zA-Z0-9=+\/]+)', '(?P<modelfile>[a-zA-Z0-9=+\/]+)', (?P<shared>[0-9]+)\) RETURNING mo_id;/";
        preg_match($pattern, $queryModel, $matches);
        
        $modelFactory = new ModelFactory($this->modelDao, $this->authorDao);
        $modelMD = $modelFactory->createModelMetadata(-1, $matches['author'], $matches['path'], $matches['name'], $matches['notes'], $matches['shared']);
        $newModel = new Model();
        $newModel->setMetadata($modelMD);
        $newModel->setModelFiles(new ModelFilesTar(base64_decode($matches['modelfile'])));
        $newModel->setThumbnail(base64_decode($matches['thumbfile']));
        
        // Retrieve OBJECT data from query
        $search = 'ob_elevoffset'; // We're searching for ob_elevoffset presence in the request to correctly preg it.
        $pos = strpos($queryObj, $search);

        if (!$pos) { // No offset is present
            $pattern  = "/INSERT INTO fgs_objects \(wkb_geometry, ob_gndelev, ob_heading, ob_country, ob_model, ob_group\) VALUES \(ST_PointFromText\('POINT\((?P<long>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), (?P<gndelev>[0-9.-]+), (?P<orientation>[0-9.-]+), '(?P<country>[a-z-A-Z-]+)', (?P<model>[a-z-A-Z_0-9-]+), 1\)/";
            preg_match($pattern, $queryObj, $matches);
            $matches['elevoffset'] = 0;
        }
        else { // ob_elevoffset has been found
            $pattern  = "/INSERT INTO fgs_objects \(wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_country, ob_model, ob_group\) VALUES \(ST_PointFromText\('POINT\((?P<long>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), (?P<gndelev>[0-9.-]+), (?P<elevoffset>[NULL0-9.-]+), (?P<orientation>[0-9.-]+), '(?P<country>[a-z-A-Z-]+)', (?P<model>[a-z-A-Z_0-9-]+), 1\)/";
            preg_match($pattern, $queryObj, $matches);
        }
        
        $objectFactory = new ObjectFactory($this->objectDao);
        $newObject = $objectFactory->createObject(-1, -1,
                $matches['long'], $matches['lat'], $matches['country'], 
                $matches['elevoffset'], $matches['orientation'], 1, "");
        
        $requestModelAdd = new RequestModelAdd();
        $requestModelAdd->setNewModel($newModel);
        $requestModelAdd->setNewObject($newObject);
        
        return $requestModelAdd;
    }
    
    private function getRequestModelUpdateFromRow($requestQuery) {
        // Retrieve data from query
        $pattern = "/UPDATE fgs_models SET mo_path \= '(?P<path>[a-zA-Z0-9_.-]+)', mo_author \= (?P<author>[0-9]+), mo_name \= '(?P<name>[a-zA-Z0-9,;:?@ !_.-]+)', mo_notes \= '(?P<notes>[a-zA-Z0-9 ,!_.-]*)', mo_thumbfile \= '(?P<thumbfile>[a-zA-Z0-9=+\/]+)', mo_modelfile \= '(?P<modelfile>[a-zA-Z0-9=+\/]+)', mo_shared \= (?P<shared>[0-9]+) WHERE mo_id \= (?P<modelid>[0-9]+)/";
        preg_match($pattern, $requestQuery, $matches);

        $modelFactory = new ModelFactory($this->modelDao, $this->authorDao);
        $modelMD = $modelFactory->createModelMetadata($matches['modelid'],
                $matches['author'], $matches['path'], $matches['name'],
                $matches['notes'], $matches['shared']);
        
        $newModel = new Model();
        $newModel->setMetadata($modelMD);
        $newModel->setModelFiles(new ModelFilesTar(base64_decode($matches['modelfile'])));
        $newModel->setThumbnail(base64_decode($matches['thumbfile']));

        // Retrieve old model
        $oldModel = $this->modelDao->getModel($modelMD->getId());
        
        $requestModelUpd = new RequestModelUpdate();
        $requestModelUpd->setNewModel($newModel);
        $requestModelUpd->setOldModel($oldModel);
        
        return $requestModelUpd;
    }
    
    private function getRequestObjectAddFromRow($requestQuery) {
        // Removing the start of the query from the data
        $triggedQuery = strstr($requestQuery, 'VALUES');
        $pattern = "/VALUES \('(?P<desc>[0-9a-zA-Z_\-. \[\]()]+)', ST_PointFromText\('POINT\((?P<long>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), (?P<elev>[0-9.-]+), (?P<elevoffset>(([0-9.-]+)|NULL)), (?P<orientation>[0-9.-]+), '(?P<country>[a-z]+)', (?P<model_id>[0-9]+), 1\)/";

        preg_match($pattern, $triggedQuery, $matches);
        $objectFactory = new ObjectFactory($this->objectDao);
        
        $newObject = $objectFactory->createObject(-1, $matches['model_id'],
                $matches['long'], $matches['lat'], $matches['country'], 
                $matches['elevoffset'], $matches['orientation'], 1, $matches['desc']);
            
        $requestObjAdd = new RequestObjectAdd();
        $requestObjAdd->setNewObject($newObject);
        
        return $requestObjAdd;
    }
    
    private function getRequestMassiveObjectsAddFromRow($requestQuery) {
        // Removing the start of the query from the data;
        $triggedQuery = str_replace("INSERT INTO fgs_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_country, ob_group) " .
                                        "VALUES (","",$requestQuery);
        // Separating the data based on the ST_PointFromText existence
        $tab_tags = explode(", (",$triggedQuery);
        $newObjects = array();
        
        $pattern = "/'(?P<notes>[a-zA-Z0-9 +,!_.;\(\)\[\]\/-]*)', ST_PointFromText\('POINT\((?P<long>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), (?P<elev>[0-9.-]+), (?P<elevoffset>[0-9.-]+), (?P<orientation>[0-9.-]+), (?P<model_id>[0-9]+), '(?P<country>[a-z]+)', 1\)/";
        foreach ($tab_tags as $value_tag) {
            preg_match($pattern, $value_tag, $matches);

            $objectFactory = new ObjectFactory($this->objectDao);
        
            $newObject = $objectFactory->createObject(-1, $matches['model_id'],
                    $matches['long'], $matches['lat'], $matches['country'], 
                    $matches['elevoffset'], $matches['orientation'], 1, $matches['notes']);
            
            $newObjects[] = $newObject;
        }
        
        $requestMassObjAdd = new RequestMassiveObjectsAdd();
        $requestMassObjAdd->setNewObjects($newObjects);
        
        return $requestMassObjAdd;
    }
    
    private function getRequestObjectUpdateFromRow($requestQuery) {
        // Removing the start of the query from the data
        $triggedQuery = strstr($requestQuery, 'SET');
        $triggedQuery = str_replace('$','',$triggedQuery);

        $pattern = "/SET ob_text\=(?P<notes>[a-zA-Z0-9 +,!_.;\(\)\[\]\/-]*), wkb_geometry\=ST_PointFromText\('POINT\((?P<lon>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), ob_gndelev\=(?P<elev>[0-9.-]+), ob_elevoffset\=(?P<elevoffset>(([0-9.-]+)|NULL)), ob_heading\=(?P<orientation>[0-9.-]+), ob_model\=(?P<model_id>[0-9]+), ob_group\=1 WHERE ob_id\=(?P<object_id>[0-9]+)/";

        preg_match($pattern, $triggedQuery, $matches);
        //$country = $matches['country'];

        $objectFactory = new ObjectFactory($this->objectDao);
        $newObject = $objectFactory->createObject($matches['object_id'], $matches['model_id'],
                $matches['lon'], $matches['lat'], 0, $matches['elevoffset'],
                $matches['orientation'], 1, $matches['notes']);
        $newObject->setGroundElevation($matches['elev']);

        $requestObjUp = new RequestObjectUpdate();
        $requestObjUp->setContributorEmail("");
        $requestObjUp->setComment("");
        $requestObjUp->setNewObject($newObject);
        $requestObjUp->setOldObject($this->objectDao->getObject($matches['object_id']));
        
        return $requestObjUp;
    }
    
    private function getRequestObjectDeleteFromRow($requestQuery) {
        $triggedQuery = strstr($requestQuery, 'WHERE');
        $triggedQuery = str_replace('$','',$triggedQuery);
        $pattern = "/WHERE ob_id\=(?P<object_id>[0-9]+)/";

        preg_match($pattern, $triggedQuery, $matches);
        $objectToDel = $this->objectDao->getObject($matches['object_id']);

        $requestObjDel = new RequestObjectDelete();
        
        // Not available with actual DAO
        $requestObjDel->setContributorEmail("");
        $requestObjDel->setComment("");
        
        $requestObjDel->setObjectToDelete($objectToDel);

        return $requestObjDel;
    }
}
?>