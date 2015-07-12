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

/**
 * Controller for model addition form
 *
 * @author Julien Nguyen
 */
class AddModelController extends ControllerMenu {
    private $objectDaoRO;
    private $authorDaoRO;
    
    public function __construct() {
        parent::__construct();
        $this->objectDaoRO = \dao\DAOFactory::getInstance()->getObjectDaoRO();
        $this->authorDaoRO = \dao\DAOFactory::getInstance()->getAuthorDaoRO();
    }
    
    /**
     * Display form action
     */
    public function formAction() {
        parent::menu();
        
        // Show all the families other than the static family
        $modelsGroups = $this->getModelsGroups();
        $countries = $this->objectDaoRO->getCountries();
        $nbModels = $this->getModelDaoRO()->countTotalModels();
        $authors = $this->authorDaoRO->getAllAuthors(0, "ALL");

        include 'view/submission/add_model_form.php';
    }
}
