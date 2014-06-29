<?php

require_once 'Request.php';

/**
 * Model addition request
 * 
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */
class RequestModelAdd extends Request {
    private $newModel;
    private $newObject;
    
    public function getNewModel() {
        return $this->newModel;
    }
    
    public function setNewModel($newModel) {
        $this->newModel = $newModel;
    }
    
    public function getNewObject() {
        return $this->newObject;
    }
    
    public function setNewObject($newObject) {
        $this->newObject = $newObject;
    }
}

?>
