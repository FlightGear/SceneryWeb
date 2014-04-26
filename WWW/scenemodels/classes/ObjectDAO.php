<?php
require_once 'PgSqlDAO.php';
require_once "IObjectDAO.php";
require_once "Object.php";

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
                                         "FROM fgs_objects WHERE ob_id=$objectId;");
        $objectRow = pg_fetch_assoc($result);
        $object = $this->getObjectFromRow($objectRow);
        
        return $object;
    }
    
    public function getObjects($pagesize, $offset, $criteria=null) {
        // Generating WHERE clause from criteria
        $whereClause = "";
        if (isset($criteria) && count($criteria)>0) {
            $whereClause = "WHERE ";
            $and = '';
            foreach ($criteria as $criterion) {
                $whereClause .= $and . $criterion->getVarName() . $criterion->getOperation() . $criterion->getValue();
                $and = ' AND ';
            }
        }
    
        $result = $this->database->query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, fn_SceneDir(wkb_geometry) AS ob_dir ".
                                         "FROM fgs_objects $whereClause ".
                                         "ORDER BY ob_modified DESC LIMIT $pagesize OFFSET $offset;");
        $result_array = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $result_array[] = $this->getObjectFromRow($row);
        }
        
        return $result_array;
    }
    
    public function getObjectsByModel($modelId) {
        $result = $this->database->query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, fn_SceneDir(wkb_geometry) AS ob_dir ".
                                         "FROM fgs_objects WHERE ob_model=$modelId ".
                                         "ORDER BY ob_modified DESC;");
        $result_array = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $result_array[] = $this->getObjectFromRow($row);
        }
        
        return $result_array;
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
        $object = new Object();
        $object->setId($objectRow["ob_id"]);
        $object->setModelId($objectRow["ob_model"]);
        $object->setLongitude($objectRow["ob_lon"]);
        $object->setLatitude($objectRow["ob_lat"]);
        $object->setDir($objectRow["ob_dir"]);
        $object->setCountry($objectRow["ob_country"]);
        $object->setGroundElevation($objectRow["ob_gndelev"]);
        $object->setElevationOffset($objectRow["ob_elevoffset"]);
        $object->setOrientation($objectRow["ob_heading"]);
        $object->setDescription($objectRow["ob_text"]);
        $object->setGroupId($objectRow["ob_group"]);
        $object->setLastUpdated(new DateTime($objectRow["ob_modified"]));
        
        return $object;
    }

}

?>
