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
 * Object addition controller
 *
 * @author Julien Nguyen
 */
class AddObjectsController extends ControllerMenu {
    private $objectDaoRO;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->objectDaoRO = \dao\DAOFactory::getInstance()->getObjectDaoRO();
    }

    /**
     * Display form action
     */
    public function formAction() {
        parent::menu();
        
        // Show all the families other than the static family
        $modelsGroups = $this->getModelsGroups();
        $countries = $this->objectDaoRO->getCountries();
        $nbObjects = $this->objectDaoRO->countObjects();

        include 'view/submission/object/add_object_form.php';
    }
    
    public function massiveformAction() {
        parent::menu();
        
        // Show all the families other than the static family
        $modelsGroups = $this->getModelsGroups();
        $countries = $this->objectDaoRO->getCountries();
        $nbObjects = $this->objectDaoRO->countObjects();

        include 'view/submission/object/mass_add_object_form.php';
    }
    
    /**
     * Check submitted object action
     */
    public function checkAction() {
        // Check captcha
        $resp = parent::checkCaptcha();
        if (!$resp->is_valid) {
            $page_title = "Automated Objects Submission Form";

            $error_text = "Sorry but the reCAPTCHA wasn't entered correctly. <a href='javascript:history.go(-1)'>Go back and try it again</a>" .
                 "<br />(reCAPTCHA complained: " . $resp->error . ")<br />".
                 "Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
            include 'view/error_page.php';
            return;
        }
        
        $error = false;
        $errors = array();
        $newObjects = array();
        $objectLinesRequests = array();
        $modelMDs = array();
        $requestDaoRW = \dao\DAOFactory::getInstance()->getRequestDaoRW();

        $i = 1;
        while ($this->getVar('modelId'.$i) != null) {
            $modelId = stripslashes($this->getVar('modelId'.$i));
            $lat = number_format(stripslashes($this->getVar('lat'.$i)),7,'.','');
            $long = number_format(stripslashes($this->getVar('long'.$i)),7,'.','');
            $countryId = $this->getVar('countryId'.$i);
            $offset = number_format(stripslashes($this->getVar('offset'.$i)),2,'.','');
            $heading = number_format(stripslashes($this->getVar('heading'.$i)),1,'.','');

            $objectValidator = \submission\ObjectValidator::getObjectValidator($modelId, $long, $lat, $countryId, $offset, $heading);
            $objErrors = $objectValidator->validate();
            
            if (count($objErrors) == 0) {
                $modelMD = $this->getModelDaoRO()->getModelMetadata($modelId);
                $modelMDs[$modelId] = $modelMD;

                $objectFactory = new \ObjectFactory($this->objectDaoRO);
                $newObject = $objectFactory->createObject(-1, $modelId, $long, $lat, $countryId, 
                $offset, \ObjectUtils::headingSTG2True($heading), 1, $modelMD->getName());

                // Detect if the object is already in the database
                if ($this->objectDaoRO->checkObjectAlreadyExists($newObject)) {
                    $objErrors[] = new \Exception("The object already exists in the database!");
                    $error = true;
                }
                
                $newObjects[] = $newObject;
                $objectLineRequest = new \model\ObjectLineRequest();
                $objectLineRequest->setObject($newObject);
                $objectLineRequest->setErrors($objErrors);
                $objectLinesRequests[$i] = $objectLineRequest;
            } else {
                $error = true;
            }
            
            $i++;
        }
        
        // Checking that comment exists. Just a small verification as it's not going into DB.
        $inputComment = stripslashes($this->getVar('comment'));
        if ($inputComment != '' && \FormChecker::isComment($inputComment)) {
            $sent_comment = $inputComment;
        }
        else {
            $errors[] = new \Exception("Comment mismatch!");
            $error = true;
        }
        
        // Checking that email is valid (if it exists).
        //(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
        $inputEmail = $this->getVar('email');
        if (\FormChecker::isEmail($inputEmail)) {
            $safe_email = htmlentities(stripslashes($inputEmail));
        }
        
        // If there is no error, insert the object to the pending requests table.
        if (!$error) {
            
            $request = new \model\RequestMassiveObjectsAdd();
            $request->setNewObjects($newObjects);
            if (isset($safe_email)) {
                $request->setContributorEmail($safe_email);
            }
            $request->setComment($sent_comment);
            
            try {
                $updatedReq = $requestDaoRW->saveRequest($request);
            } catch (\Exception $e) {
                $page_title = "Objects addition Form";
                $error_text = "Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.<br />";
                include 'view/error_page.php';
                return;
            }
            
            // Sending mail if there is no false and SQL was correctly inserted.
            // Sets the time to UTC.
            date_default_timezone_set('UTC');
            $dtg = date('l jS \of F Y h:i:s A');

            // Retrieving the IP address of the submitter (takes some time to resolve the IP address though).
            $ipaddr = htmlentities(stripslashes($_SERVER["REMOTE_ADDR"]));
            $host = gethostbyaddr($ipaddr);
            
            $emailSubmit = \EmailContentFactory::getObjectsAddRequestPendingEmailContent($dtg, $ipaddr, $host, $updatedReq);
            $emailSubmit->sendEmail("", true);

            // Mailing the submitter to tell that his submission has been sent for validation.
            if (isset($safe_email)) {
                $emailSubmit = \EmailContentFactory::getObjectsAddSentForValidationEmailContent($ipaddr, $host, $dtg, $updatedReq);
                $emailSubmit->sendEmail($safe_email, false);
            }
        }

        include 'view/submission/object/check_add.php';
    }
    
    /**
     * Checks if models exists in DB from a model name sent in parameter.
     * @global type $modelDaoRO
     * @param string $modelFullPath Model's path is composed of:
     *        OBJECT_SHARED Models/mg_path from fgs_modelgroups/mo_path from fgs_models
     *        ie : Models/Power/windturbine.xml
     * @return ModelMetadata
     * @throws Exception if model is not found
     */
    private function getModelFromSTG($modelFullPath) {
        global $modelDaoRO;

        // Explodes the fields of the string separated by /
        $tabPath = explode("/", $modelFullPath);

        // Checking that the label "Model" is correct
        if (strcmp($tabPath[0],"Models") != 0) {
            throw new Exception("Bad model label!");
        }

        // Counts the number of fields.
        $maxTabPath = count($tabPath);

        // Returns the last field value.
        $queriedMoPath = $tabPath[$maxTabPath-1];

        // Get the model (throw exception if not found)
        $modelMD = $this->getModelDaoRO()->getModelMetadataFromName($queriedMoPath);

        // Now proceeding with the family
        // The family path is the string between Models and the object name. Can be multiple.
        $queriedFamilyPath = "";
        for ($j=1; $j<$maxTabPath-1; $j++) {
            $queriedFamilyPath .= $tabPath[$j]."/";
        }

        $modelsGroup = $this->getModelDaoRO()->getModelsGroupByPath($queriedFamilyPath);

        if ($modelsGroup->getId() != $modelMD->getModelsGroup()->getId()) {
            throw new \Exception("No $queriedMoPath found in $queriedFamilyPath!");
        }

        return $modelMD;
    }

    private function createObjectLineRequest($line, $objectFactory) {
        $objectLineRequest = new \model\ObjectLineRequest();
        $objectLineRequest->setStgLine($line);

        $elevoffset = 0;
        $tab_tags = explode(" ", $line);

        $errors = array();
        $warnings = array();

        // TODO : Have also to check the number of tab_tags returned!

        // Checking Label (must contain only letters and be strictly labelled OBJECT_SHARED for now)
        if (strcmp($tab_tags[0], "OBJECT_SHARED") != 0) {
            $errors[] = new \Exception("Only OBJECT_SHARED is supported!");
        }

        // Checking model (Contains only figures, letters, _/. and must exist in DB)
        $path = $tab_tags[1];
        if (\FormChecker::isFilePath($path)) {
            try {
                $modelMD = $this->getModelFromSTG($path);
                $modelId = $modelMD->getId();
            } catch (\Exception $ex) {
                $errors[] = $ex;
            }
        }
        else {
            $errors[] = new \Exception("Model Error!");
        }

        // Longitude
        $long = $tab_tags[2];

        // Latitude
        $lat = $tab_tags[3];

        // Elevation (TODO: can be used to automatically compute offset!!)
        //$gndelev = $value_tag;

        // Orientation
        $orientation = $tab_tags[5];

        //If 7 columns, it's the offset. if 8 columns, it's pitch
        if (count($tab_tags) == 7) {
            $elevoffset = $tab_tags[6];
        }

        // Country
        $countryId = $this->objectDaoRO->getCountryAt($long, $lat)->getCode();

        $objectValidator = \submission\ObjectValidator::getObjectValidator($modelId, $long, $lat, $countryId, $elevoffset, $orientation);
        $errors = array_merge($errors, $objectValidator->validate());

        if (count($errors) == 0) {
            $newObject = $objectFactory->createObject(-1, $modelId, $long, $lat, $countryId, 
                        $elevoffset, \ObjectUtils::headingSTG2True($orientation), 1, $modelMD->getName());
            $objectLineRequest->setObject($newObject);

            if ($this->objectDaoRO->checkObjectAlreadyExists($newObject)) {
                $errors[] = new \Exception('Object exists already!');
            } else if ($this->objectDaoRO->detectNearbyObjects($lat, $long, $modelId)) {
                $warnings[] = new \Exception('Nearby object');
            }
        }

        $objectLineRequest->setErrors($errors);
        $objectLineRequest->setWarnings($warnings);

        return $objectLineRequest;
    }
    
    function confirmMassAction() {
        // Checking that email is valid (if it exists).
        if (\FormChecker::isEmail($this->getVar('email'))) {
            $safe_email = htmlentities(stripslashes($this->getVar('email')));
        }

        // Checking that comment exists. Just a small verification as it's not going into DB.
        if (\FormChecker::isComment($this->getVar('comment'))) {
            $sent_comment = htmlentities(stripslashes($this->getVar('comment')));
        }
        
        // Checking that stg exists and is containing only letters or figures.
        if (isset($_POST['stg']) && \FormChecker::isStgLines($_POST['stg'])) {
            $page_title = "Automated Objects Mass Import Submission Form";

            $error_text = "I'm sorry, but it seems that the content of your STG file is not correct (bad characters?). Please check again.";
            include 'view/error_page.php';
            return;
        }
        
        $tab_lines = explode("\n", $_POST['stg']);          // Exploding lines by carriage return (\n) in submission input.
        $tab_lines = array_map('trim', $tab_lines);         // Trim lines.
        $tab_lines = array_filter($tab_lines);              // Removing blank lines.
        $tab_lines = array_slice($tab_lines, 0, 100);       // Selects the 100 first elements of the tab (the 100 first lines not blank)

        $nb_lines = count($tab_lines);
        
        if ($nb_lines < 1) {
            $page_title = "Automated Objects Mass Import Submission Form";
            
            $error_text = "Not enough lines were submitted: 1 line minimum per submission!";
            include 'view/error_page.php';
            exit;
        }
        $i = 1;
        $countries = $this->objectDaoRO->getCountries();
        $objectFactory = new \ObjectFactory($this->objectDaoRO);
        $objectLinesRequests = array();
        $modelMDs = array();

        // Check each line
        foreach ($tab_lines as $line) {
            $objLineReq = $this->createObjectLineRequest($line, $objectFactory);
            if ($objLineReq->getObject() != null) {
                $modelId = $objLineReq->getObject()->getModelId();
                $modelMDs[$modelId] = $this->getModelDaoRO()->getModelMetadata($modelId);
            }
            
            $objectLinesRequests[$i] = $objLineReq;
            $i++;
        }
        
        include 'view/submission/object/mass_add_object_form_confirm.php';
    }
}
