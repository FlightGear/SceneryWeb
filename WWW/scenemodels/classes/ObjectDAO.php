<?php
require_once 'PgSqlDAO.php';
require_once "IObjectDAO.php";
require_once "Object.php";
require_once "Country.php";
require_once "ObjectsGroup.php";

/**
 * Object Data Access Object implementation for PostgreSQL
 *
 * Database layer to access objects from PostgreSQL database
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */

class ObjectDAO extends PgSqlDAO implements IObjectDAO {    
    public function addObject($object) {
        //TODO
    }

    public function updateObject($object) {
        //TODO
    }
    
    public function getObject($objectId) {
        $result = $this->database->query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, fn_SceneDir(wkb_geometry) AS ob_dir ".
                                         "FROM fgs_objects, fgs_countries WHERE ob_id=".$objectId." AND ob_country = co_code;");
        if (pg_num_rows($result) == 0) {
            throw new Exception('No object with id '. $objectId. ' was found!');
        }
        
        $objectRow = pg_fetch_assoc($result);
        return $this->getObjectFromRow($objectRow);
    }
    
    public function getObjectsAt($long, $lat) {
        $result = $this->database->query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, fn_SceneDir(wkb_geometry) AS ob_dir ".
                                         "FROM fgs_objects, fgs_countries WHERE wkb_geometry = ST_PointFromText('POINT(".$long." ".$lat.")', 4326) AND ob_country = co_code;");
    
        $resultArray = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $resultArray[] = $this->getObjectFromRow($row);
        }
        
        return $resultArray;
    }
    
    public function getObjects($pagesize, $offset, $criteria=null) {
        // Generating WHERE clause from criteria
        $whereClause = "";
        if (isset($criteria) && count($criteria)>0) {
            $whereClause = "";
            $and = ' AND ';
            foreach ($criteria as $criterion) {
                $whereClause .= $and . $criterion->getVarName() 
                                . $criterion->getOperation()
                                . $criterion->getValue();
            }
        }
    
        $result = $this->database->query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, fn_SceneDir(wkb_geometry) AS ob_dir ".
                                         "FROM fgs_objects, fgs_countries WHERE ob_country = co_code $whereClause ".
                                         "ORDER BY ob_modified DESC LIMIT ".$pagesize." OFFSET ".$offset.";");
        $resultArray = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $resultArray[] = $this->getObjectFromRow($row);
        }
        
        return $resultArray;
    }
    
    public function getObjectsByModel($modelId) {
        $result = $this->database->query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, fn_SceneDir(wkb_geometry) AS ob_dir ".
                                         "FROM fgs_objects, fgs_countries WHERE ob_model=$modelId AND ob_country = co_code ".
                                         "ORDER BY ob_modified DESC;");
        $resultArray = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $resultArray[] = $this->getObjectFromRow($row);
        }
        
        return $resultArray;
    }
    
    public function getObjectsGroup($objectGroupId) {
        $result = $this->database->query("SELECT gp_id, gp_name FROM fgs_groups ".
                                         "WHERE gp_id=".$objectGroupId.";");
        
        $row = pg_fetch_assoc($result);
        return $this->getObjectGroupFromRow($row);
    }
    
    public function getObjectsGroups() {
        $result = $this->database->query("SELECT gp_id, gp_name FROM fgs_groups;");
        
        $resultArray = array();
        
        while ($row = pg_fetch_assoc($result)) {
            $resultArray[] = $this->getObjectGroupFromRow($row);
        }
        
        return $resultArray;
    }
    
    public function getCountries() {
        $result = $this->database->query("SELECT * FROM fgs_countries ORDER BY co_name;");
        
        $resultArray = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $resultArray[] = $this->getCountryFromRow($row);
        }
        
        return $resultArray;
    }
    
    public function countObjects() {
        $result = $this->database->query("SELECT count(*) AS number FROM fgs_objects;");
        $row = pg_fetch_assoc($result);
        
        return $row["number"];
    }
    
    public function countObjectsByModel($modelId) {
        $result = $this->database->query("SELECT COUNT(*) AS number " .
                                        "FROM fgs_objects " .
                                        "WHERE ob_model=$modelId");
        $row = pg_fetch_assoc($result);
        
        return $row["number"];
    }
    
    private function getObjectFromRow($objectRow) {
        $country = $this->getCountryFromRow($objectRow);
        
        $object = new Object();
        $object->setId($objectRow["ob_id"]);
        $object->setModelId($objectRow["ob_model"]);
        $object->setLongitude($objectRow["ob_lon"]);
        $object->setLatitude($objectRow["ob_lat"]);
        $object->setDir($objectRow["ob_dir"]);
        $object->setCountry($country);
        $object->setGroundElevation($objectRow["ob_gndelev"]);
        $object->setElevationOffset($objectRow["ob_elevoffset"]);
        $object->setOrientation($objectRow["ob_heading"]);
        $object->setDescription($objectRow["ob_text"]);
        $object->setGroupId($objectRow["ob_group"]);
        $object->setLastUpdated(new DateTime($objectRow["ob_modified"]));
        
        return $object;
    }
    
    private function getCountryFromRow($countryRow) {
        $country = new Country();
        $country->setCode($countryRow["co_code"]);
        $country->setName($countryRow["co_name"]);
        $country->setCodeThree($countryRow["co_three"]);
        
        return $country;
    }
    
    private function getObjectGroupFromRow($objGroupRow) {
        $objectsGroup = new ObjectsGroup();
        $objectsGroup->setId($objGroupRow["gp_id"]);
        $objectsGroup->setName($objGroupRow["gp_name"]);
        
        return $objectsGroup;
    }

}

?>