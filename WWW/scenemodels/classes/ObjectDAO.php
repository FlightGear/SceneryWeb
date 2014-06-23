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
        
        $objectRow = pg_fetch_assoc($result);
        
        if (!$objectRow) {
            throw new Exception('No object with id '. $objectId. ' was found!');
        }
        
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
    
    public function getObjects($pagesize, $offset, $criteria=null, $orderby="ob_modified", $order="DESC") {
        $whereClause = $this->generateWhereClauseCriteria($criteria);
        if ($whereClause != "") {
            $whereClause .= " AND"; 
        }
    
        $result = $this->database->query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, fn_SceneDir(wkb_geometry) AS ob_dir ".
                                         "FROM fgs_objects, fgs_countries WHERE $whereClause ob_country = co_code ".
                                         "ORDER BY ".$orderby." $order LIMIT ".$pagesize." OFFSET ".$offset.";");
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
    
    public function getCountry($countryCode) {
        $result = $this->database->query("SELECT * FROM fgs_countries WHERE co_code='". $countryCode ."';");
              
        $row = pg_fetch_assoc($result);
        return $this->getCountryFromRow($row);
    }
    
    public function getCountryAt($long, $lat) {
        $query = "SELECT co_code, co_name, co_three FROM gadm2, fgs_countries " .
                 "WHERE ST_Within(ST_PointFromText('POINT(".
                 pg_escape_string($long)." ".pg_escape_string($lat).")', 4326), gadm2.wkb_geometry) AND gadm2.iso ILIKE fgs_countries.co_three;";
        $result = $this->database->query($query);

        $row = pg_fetch_assoc($result);
        return $this->getCountryFromRow($row);
    }
    
    public function getCountries() {
        $result = $this->database->query("SELECT * FROM fgs_countries ORDER BY co_name;");
        
        $resultArray = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $country = $this->getCountryFromRow($row);
            $resultArray[$country->getCode()] = $country;
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
                                        "WHERE ob_model=".$modelId.";");
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