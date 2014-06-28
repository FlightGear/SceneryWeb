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

require_once 'RequestNotFoundException.php';

/**
 * Request DAO
 *
 * @author Julien Nguyen <julien.nguyen3@gmail.com>
 */
class RequestDAO extends PgSqlDAO implements IRequestDAO {
    private $objectDao;
    
    public function __construct(PGDatabase $database, ObjectDAO $objectDao) {
        parent::__construct($database);
        $this->objectDao = $objectDao;
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
            if (substr_count($requestQuery,"ST_PointFromText") == 1) {
                $request = $this->getRequestObjectAddFromRow($requestQuery);
            }
            // Else, it is a mass insertion
            else {
                $request = $this->getRequestMassiveObjectsAddFromRow($requestQuery);
            }
        }
        
        // Add model request
        if (substr_count($requestQuery,"INSERT INTO fgs_models") == 1) {
            
        }
        
        // Update model request
        if (substr_count($requestQuery,"UPDATE fgs_models") == 1) {
            
        }
        
        $request->setSig($requestRow["spr_hash"]);
        
        return $request;
    }
    
    private function getRequestObjectAddFromRow($requestQuery) {
        // Removing the start of the query from the data
        $trigged_query_rw = strstr($requestQuery, 'ST_PointFromText');
        $pattern = "/ST_PointFromText\('POINT\((?P<long>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), (?P<elev>[0-9.-]+), (?P<elevoffset>(([0-9.-]+)|NULL)), (?P<orientation>[0-9.-]+), '(?P<country>[a-z]+)', (?P<model_id>[0-9]+), 1\)/";

        preg_match($pattern, $trigged_query_rw, $matches);
        $objectFactory = new ObjectFactory($this->objectDao);
        
        $newObject = $objectFactory->createObject(-1, $matches['model_id'],
                $matches['long'], $matches['lat'], $matches['country'], 
                $matches['elevoffset'], $matches['orientation'], 1, "");
            
        $requestObjAdd = new RequestObjectAdd();
        $requestObjAdd->setNewObject($newObject);
        
        return $requestObjAdd;
    }
    
    private function getRequestMassiveObjectsAddFromRow($requestQuery) {
        // Removing the start of the query from the data;
        $trigged_query_rw = str_replace("INSERT INTO fgs_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_country, ob_group) " .
                                        "VALUES (","",$requestQuery);
        // Separating the data based on the ST_PointFromText existence
        $tab_tags = explode(", (",$trigged_query_rw);
        $newObjects = array();
        
        $pattern = "/'', ST_PointFromText\('POINT\((?P<long>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), (?P<elev>[0-9.-]+), (?P<elevoffset>[0-9.-]+), (?P<orientation>[0-9.-]+), (?P<model_id>[0-9]+), '(?P<country>[a-z]+)', 1\)/";
        foreach ($tab_tags as $value_tag) {
            preg_match($pattern, $value_tag, $matches);

            $objectFactory = new ObjectFactory($this->objectDao);
        
            $newObject = $objectFactory->createObject(-1, $matches['model_id'],
                    $matches['long'], $matches['lat'], $matches['country'], 
                    $matches['elevoffset'], $matches['orientation'], 1, "");
            
            $newObjects[] = $newObject;
        }
        
        $requestMassObjAdd = new RequestMassiveObjectsAdd();
        $requestMassObjAdd->setNewObjects($newObjects);
        
        return $requestMassObjAdd;
    }
    
    private function getRequestObjectUpdateFromRow($requestQuery) {
        // Removing the start of the query from the data
        $trigged_query_rw = strstr($requestQuery, 'SET');
        $trigged_query_rw = str_replace('$','',$trigged_query_rw);

        $pattern = "/SET ob_text\=(?P<notes>[a-zA-Z0-9 +,!_.;\(\)\[\]\/-]*), wkb_geometry\=ST_PointFromText\('POINT\((?P<lon>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), ob_gndelev\=(?P<elev>[0-9.-]+), ob_elevoffset\=(?P<elevoffset>(([0-9.-]+)|NULL)), ob_heading\=(?P<orientation>[0-9.-]+), ob_model\=(?P<model_id>[0-9]+), ob_group\=1 WHERE ob_id\=(?P<object_id>[0-9]+)/";

        preg_match($pattern, $trigged_query_rw, $matches);
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
        $trigged_query_rw = strstr($requestQuery, 'WHERE');
        $trigged_query_rw = str_replace('$','',$trigged_query_rw);
        $pattern = "/WHERE ob_id\=(?P<object_id>[0-9]+)/";

        preg_match($pattern, $trigged_query_rw, $matches);
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