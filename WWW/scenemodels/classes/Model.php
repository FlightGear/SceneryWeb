<?php

/**
 * Model
 *
 * Contains metadata and model files.
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */
 
class Model {
    private $modelMetadata;
    private $modelFiles;
    
    function __construct() {
    }
    
    public function getModelFiles() {
        return $this->modelFiles;
    }
    
    public function setModelFiles($modelFiles) {
        $this->modelFiles = $modelFiles;
    }
    
    public function getMetadata() {
        return $this->modelMetadata;
    }
    
    public function setMetadata($modelMetadata) {
        $this->modelMetadata = $modelMetadata;
    }
    
}

?>
