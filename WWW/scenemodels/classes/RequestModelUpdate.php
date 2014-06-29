<?php

require_once 'Request.php';

/**
 * Model update request
 * 
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */
class RequestModelUpdate extends Request {
    private $newModel;
    private $oldModel;
    
    public function getNewModel() {
        return $this->newModel;
    }
    
    public function setNewModel($newModel) {
        $this->newModel = $newModel;
    }
    
    public function getOldModel() {
        return $this->oldModel;
    }
    
    public function setOldModel($oldModel) {
        $this->oldModel = $oldModel;
    }
}

?>
