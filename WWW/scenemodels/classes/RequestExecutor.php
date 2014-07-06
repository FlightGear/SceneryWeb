<?php

/*
 * Copyright (C) 2014 FlightGear Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Description of RequestExecutor
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 */
class RequestExecutor {
    private $modelDAO;
    private $objectDAO;
    
    public function __construct($modelDAO, $objectDAO) {
        $this->modelDAO = $modelDAO;
        $this->objectDAO = $objectDAO;
    }
    
    public function executeRequest($request) {
        switch (get_class($request)) {
        case "RequestObjectAdd":
            return $this->executeRequestObjectAdd($request);
            break;
        
        case "RequestObjectUpdate":
            $this->executeRequestObjectUpdate($request);
            break;
        
        case "RequestObjectDelete":
            $this->executeRequestObjectDelete($request);
            break;
        
        case "RequestMassiveObjectsAdd":
            return $this->executeRequestMassiveObjectsAdd($request);
            break;
        
        case "RequestModelAdd":
            return $this->executeRequestModelAdd($request);
            break;
        
        case "RequestModelUpdate":
            $this->executeRequestModelUpdate($request);
            break;
        
        default:
            throw new Exception("Not a request!");
        }
    }
    
    private function executeRequestObjectAdd($request) {
        $newObj = $request->getNewObject();
        $newObjWithId = $this->objectDAO->addObject($newObj);
        
        return $newObjWithId;
    }
    
    private function executeRequestObjectUpdate($request) {
        $newObj = $request->getNewObject();
        $this->objectDAO->updateObject($newObj);
    }
    
    private function executeRequestObjectDelete($request) {
        $objId = $request->getObjectToDelete()->getId();
        $this->objectDAO->deleteObject($objId);
    }
    
    private function executeRequestMassiveObjectsAdd($request) {
        $objsWithId = array();
        
        foreach ($request->getNewObjects() as $newObj) {
            $objsWithId[] = $this->objectDAO->addObject($newObj);
        }
        
        return $objsWithId;
    }
    
    private function executeRequestModelAdd($request) {
        $newModel = $request->getNewModel();
        $newModelWithId = $this->modelDAO->addModel($newModel);
        
        $newObject = $request->getNewObject();
        $newObject->setId($newModelWithId->getId());
        $this->objectDAO->addObject($newObject);
    }
    
    private function executeRequestModelUpdate($request) {
        $newModel = $request->getNewModel();
        $this->modelDAO->updateModel($newModel);
    }
}
