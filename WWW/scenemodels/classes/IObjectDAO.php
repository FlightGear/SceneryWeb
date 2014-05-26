<?php

/**
 * Interface for Object instance Data Access Object
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */

interface IObjectDAO {

    public function addObject($object);

    public function updateObject($object);
    
    public function getObject($objectId);
    
    public function getObjectsAt($long, $lat);
    
    public function getObjects($pagesize, $offset, $criteria, $orderby, $order);
    
    public function getObjectsByModel($modelId);
    
    public function getObjectsGroup($objectGroupId);
    
    public function getObjectsGroups();
    
    public function getCountry($countryCode);
    
    public function getCountries();
    
    public function countObjects();
    
    public function countObjectsByModel($modelId);

}

?>
