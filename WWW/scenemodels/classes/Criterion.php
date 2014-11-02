<?php

/**
 * Criterion for database query
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */

class Criterion {
    private $varName;
    private $operation;
    private $value;
    
    const OPERATION_EQ = "=";
    const OPERATION_GE = ">=";
    const OPERATION_LE = "<=";
    const OPERATION_GT = ">";
    const OPERATION_LT = "<";
    const OPERATION_LIKE = " LIKE ";
    const OPERATION_LIKE_BEGIN = "LIKEBEGIN";
    const OPERATION_LIKE_END = "LIKEEND";
    const OPERATION_IS = " is ";
    
    /**
     * Constructor
     * 
     * @param string $varName
     * @param string $operation
     * @param type $value
     */
    public function __construct($varName, $operation, $value) {
        $this->varName = $varName;
        $this->operation = $operation;
        $this->value = $value;
    }
    
    public function getVarName() {
        return $this->varName;
    }
    
    public function setVarName($varName) {
        $this->varName = $varName;
    }
    
    public function getOperation() {
        return $this->operation;
    }
    
    public function setOperation($operation) {
        $this->operation = $operation;
    }
    
    public function getValue() {
        return $this->value;
    }
    
    public function setValue($value) {
        $this->value = $value;
    }
}

?>