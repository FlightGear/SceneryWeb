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
 * Controller for objects updates
 *
 * @author Julien Nguyen
 */
class UpdateObjectsController extends ControllerMenu {
    private $objectDaoRO;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->objectDaoRO = \dao\DAOFactory::getInstance()->getObjectDaoRO();
    }
    
    /**
     * Displays find form action
     */
    public function findformAction() {
        parent::menu();
        
        include 'view/submission/find_obj_update_form.php';
    }
    
    /**
     * Displays the find results action
     */
    public function findObjWithPosAction() {
        parent::menu();
        
        $error = false;

        // Checking that latitude and longitude are correct
        $error_text = "";
        $long = number_format(stripslashes($this->getVar('longitude')),7,'.','');
        $lat = number_format(stripslashes($this->getVar('latitude')),7,'.','');
        if (!\FormChecker::isLatitude($lat)) {
            $error_text .= "Latitude mismatch!<br/>";
            $error = true;
        }

        // Checking that longitude exists and is containing only digits, - or ., is >=-180 and <=180 and with correct decimal format.
        if (!\FormChecker::isLongitude($long)) {
            $error_text .= "Longitude mismatch!<br/>";
            $error = true;
        }


        if ($error) {
            $page_title = "Automated Objects Update Form";
            // $error_text is defined above
            include 'view/error_page.php';
            return;
        }
        
        // Let's see in the database if something exists at this position
        $objects = $this->objectDaoRO->getObjectsAt($long, $lat);

        if (count($objects) == 0) {
            $page_title  = "Automated Objects Update Form";
            $error_text  = "Sorry, but no object was found at position longitude: ".$long.", latitude: ".$lat.".";
            $advise_text = "Please <a href='javascript:history.go(-1)'>go back and check your position</a> (see in the relevant STG file).";
            include 'view/error_page.php';
            return;
        }
        
        $modelMetadatas = array();
        foreach ($objects as $object) {
            $modelId = $object->getModelId();
            $modelMetadatas[$modelId] = $this->getModelDaoRO()->getModelMetadata($modelId);
        }
        
        include 'view/submission/select_obj_update_form.php';
    }
    
    /**
     * Update form action
     */
    public function updateFormAction() {
        parent::menu();
        
        $id_to_update = $this->getVar('id_to_update');
        $objectToUp = $this->objectDaoRO->getObject($id_to_update);
        $modelMDToUp = $this->getModelDaoRO()->getModelMetadata($objectToUp->getModelId());
        $countries = $this->objectDaoRO->getCountries();
        
        include 'view/submission/update_object_form.php';
    }
    
    /**
     * Check object update request action
     */
    public function checkAction() {
        // Captcha stuff
        $resp = parent::checkCaptcha();
        if (!$resp->is_valid) {
            $page_title = "Automated Objects Update Form";
            $error_text = "<br />Sorry but the reCAPTCHA wasn't entered correctly.".
                          " <a href='javascript:history.go(-1)'>Go back and try it again</a>" .
                          "<br />(reCAPTCHA complained: " . $resp->error . ")<br />" .
                          "Don't forget to feed the Captcha, it's a mandatory item as well.".
                          " Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
            include 'view/error_page.php';
            return;
        }
        
        $requestDaoRW = \dao\DAOFactory::getInstance()->getRequestDaoRW();

        // Checking all variables
        $modelId = $this->getVar('modelId');
        $new_long = $this->getVar('new_long');
        $new_lat = $this->getVar('new_lat');
        $new_country = $this->getVar('new_country');
        $new_offset = $this->getVar('new_offset');
        $new_orientation = $this->getVar('new_heading');
        $safe_new_ob_text = htmlentities(stripslashes($this->getVar('new_ob_text')));
        
        if (\FormChecker::isObjectId($this->getVar('id_to_update'))) {
            $id_to_update = $this->getVar('id_to_update');
        } else {
            $page_title = 'Automated Objects Update Form';
            $error_text = 'Object ID is wrong';
            include 'view/error_page.php';
            return;
        }
        
        $objectValidator = \submission\ObjectValidator::getObjectValidator($modelId, $new_long, $new_lat, $new_country, $new_offset, $new_orientation);
        $errors = $objectValidator->validate();
        
        $safe_email = null;
        $inputEmail = $this->getVar('email');
        if ($inputEmail != null) {
            if (\FormChecker::isEmail($inputEmail)) {
                $safe_email = htmlentities(stripslashes($this->getVar('email')));
            } else {
                $errors[] = new \Exception('Email mismatch!');
            }
            
        }
        
        if (isset($_POST['comment']) && \FormChecker::isComment($_POST['comment'])) {
            $comment = $_POST['comment'];
        } else {
            $comment = "";
        }
        

        // Final step to edition
        if (count($errors) == 0) {
            $objectFactory = new \ObjectFactory($this->objectDaoRO);
            $oldObject = $this->objectDaoRO->getObject($id_to_update);
            $newObject = $objectFactory->createObject($id_to_update, $modelId,
                    $new_long, $new_lat, $new_country, 
                    $new_offset, \ObjectUtils::headingSTG2True($new_orientation), 1, $safe_new_ob_text);

            $oldModelMD = $this->getModelDaoRO()->getModelMetadata($oldObject->getModelId());
            $newModelMD = $this->getModelDaoRO()->getModelMetadata($modelId);

            $request = new \model\RequestObjectUpdate();
            $request->setNewObject($newObject);
            $request->setOldObject($oldObject);
            $request->setContributorEmail($safe_email);
            $request->setComment($comment);

            try {
                $updatedReq = $requestDaoRW->saveRequest($request);
            } catch (Exception $e) {
                $error_text = "Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.";
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

            $emailSubmit = \EmailContentFactory::getObjectUpdateRequestPendingEmailContent($dtg, $ipaddr, $host, $oldModelMD, $newModelMD, $updatedReq);
            $emailSubmit->sendEmail("", true);

            // Mailing the submitter to tell him that his submission has been sent for validation.
            if (!$safe_email) {
                $emailSubmit = \EmailContentFactory::getObjectUpdateRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $updatedReq, $oldModelMD, $newModelMD);
                $emailSubmit->sendEmail($safe_email, false);
            }
        }
        
        include 'view/submission/check_update.php';
    }
}
