<?php

namespace model;

/**
 * Object instance of a model
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */
class Object {
    private $objectId = 0;
    private $modelId;
    
    private $country;
    
    private $dir;
    
    private $position;
    private $groundElevation;
    private $elevationOffset;
    
    private $lastUpdated;
    
    private $description;
    private $groupId;
    
    function __construct() {
        $this->position = new Position();
    }
    
    public function getId() {
        return $this->objectId;
    }
    
    public function setId($objectId) {
        $this->objectId = $objectId;
    }
    
    public function getModelId() {
        return $this->modelId;
    }
    
    public function setModelId($modelId) {
        $this->modelId = $modelId;
    }
    
    public function getDir() {
        return $this->dir;
    }
    
    public function setDir($dir) {
        $this->dir = $dir;
    }
    
    public function getCountry() {
        return $this->country;
    }
    
    public function setCountry($country) {
        $this->country = $country;
    }

    public function getGroundElevation() {
        return $this->groundElevation;
    }
    
    public function setGroundElevation($groundElevation) {
        $this->groundElevation = $groundElevation;
    }
    
    public function getElevationOffset() {
        if (!$this->elevationOffset) {
            return 0;
        } else {
            return $this->elevationOffset;
        }
    }
    
    public function setElevationOffset($elevationOffset) {
        $this->elevationOffset = $elevationOffset;
    }

    /**
     * Gets object's description
     * @return object's description string
     */
    public function getDescription() {
        return $this->description;
    }
    
    public function setDescription($description) {
        $this->description = $description;
    }
    
    public function getGroupId() {
        return $this->groupId;
    }
    
    public function setGroupId($groupId) {
        $this->groupId = $groupId;
    }
    
    
    public function getLastUpdated() {
        return $this->lastUpdated;
    }
    
    public function setLastUpdated($lastUpdated) {
        $this->lastUpdated = $lastUpdated;
    }
    
    public function getPosition() {
        return $this->position;
    }
}

?>