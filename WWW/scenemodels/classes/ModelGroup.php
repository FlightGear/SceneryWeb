<?php

/**
 * Model group
 *
 * Contains information about a group of models
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */

class ModelGroup {
    private $modelGroupId;
    private $name;
    private $path;
    
    function __construct() {
    }
    
    public function getId() {
        return $this->modelGroupId;
    }
    
    public function setId($modelGroupId) {
        return $this->modelGroupId = $modelGroupId;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function setName($name) {
        return $this->name = $name;
    }
    
    public function getPath() {
        return $this->path;
    }
    
    public function setPath($path) {
        return $this->path = $path;
    }
    
    public function isStatic() {
        return $this->modelGroupId == 0;
    }
}

?>