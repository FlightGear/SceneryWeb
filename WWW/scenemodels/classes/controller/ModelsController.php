<?php

/* 
 * Copyright (C) 2015 FlightGear Team
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

namespace controller;

class ModelsController extends ControllerMenu {
    private $objectDaoRO;

    public function __construct() {
        parent::__construct();
        $this->objectDaoRO = \dao\DAOFactory::getInstance()->getObjectDaoRO();
    }
    
    /**
     * Action for models browsing
     */
    public function browseAction() {
        $modelGroupId = $this->getVar('shared');
        $offset = $this->getVar('offset');
        $pagesize = 99;
        
        if ($offset == null) {
            $offset = 0;
        }
        
        if ($modelGroupId != null && $modelGroupId >= 0) {
            $group = $this->getModelDaoRO()->getModelsGroup($modelGroupId);
            $title = "Model Browser: ".$group->getName();
            $modelMetadatas = $this->getModelDaoRO()->getModelMetadatasByGroup($modelGroupId, $offset, $pagesize);
        } else {
            $modelMetadatas = $this->getModelDaoRO()->getModelMetadatas($offset, $pagesize);
            $title = "FlightGear Scenery Model Browser";
        }
        
        include 'view/modelbrowser.php';
    }
    
    /**
     * Action for model view
     */
    public function viewAction() {
        $id = $this->getVar('id');
        if (\FormChecker::isModelId($id)) {
            $modelMetadata = $this->getModelDaoRO()->getModelMetadata($id);
            $occurences = $this->objectDaoRO->countObjectsByModel($id);
            
            include 'view/modelview.php';
        } else {
            $pageTitle = "Model ID not valid";
            $errorText = "Sorry, but the model ID you are asking is not valid.";
            include 'view/error_page.php';
        }
    }
    
    public function modelViewerAction() {
        $id = $this->getVar('id');
        if (empty($id) || !\FormChecker::isModelId($id)) {
            return;
        }
        
        $ac3DFile = "get_model_files.php?type=ac&id=".$id;
        $texturePrefix = 'get_model_files.php?type=texture&id='.$id.'&name=';
        include 'view/model_viewer.php';
    }
}