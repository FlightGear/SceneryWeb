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

        include 'view/submission/add_object_form.php';
    }
    
    public function massiveformAction() {
        parent::menu();
        
        // Show all the families other than the static family
        $modelsGroups = $this->getModelsGroups();
        $countries = $this->objectDaoRO->getCountries();
        $nbObjects = $this->objectDaoRO->countObjects();

        include 'view/submission/mass_add_object_form.php';
    }
    
    /**
     * Check submitted object action
     */
    public function checkAction() {
        // What happens when the CAPTCHA was entered incorrectly
        if (!parent::checkCaptcha()) {
            $page_title = "Automated Objects Submission Form";

            $error_text = "<br />Sorry but the reCAPTCHA wasn't entered correctly. <a href='javascript:history.go(-1)'>Go back and try it again</a>" .
                 "<br />(reCAPTCHA complained: " . $resp->error . ")<br />".
                 "Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
            include 'view/error_page.php';
            return;
        }
        
        $requestDaoRW = \dao\DAOFactory::getInstance()->getRequestDaoRW();

        $error = false;
        
        $inputModelId = $this->getVar('modelId');
        if (\FormChecker::isModelId($inputModelId)) {
            $model_id = pg_escape_string(stripslashes($inputModelId));
            $modelMD = $this->getModelDaoRO()->getModelMetadata($model_id);
        } else {
            $error = true;
        }
        
        $inputLat = $this->getVar('latitude');
        if (\FormChecker::isLatitude($inputLat)) {
            $lat = number_format(stripslashes($inputLat),7,'.','');
        } else {
            $error = true;
        }
        
        $inputLong = $this->getVar('longitude');
        if (\FormChecker::isLongitude($inputLong)) {
            $long = number_format(stripslashes($inputLong),7,'.','');
        } else {
            $error = true;
        }
        
        // Country.
        $inputObCountry = $this->getVar('ob_country');
        if (\FormChecker::isCountryId($inputObCountry)) {
            $ob_country = $inputObCountry;
            $country = $this->objectDaoRO->getCountry($ob_country);
        }
        else {
            $error = true;
        }


        // Checking that offset exists and is containing only digits, - or ., is >=-10000 and <=10000 and with correct decimal format.
        if (\FormChecker::isOffset($this->getVar('offset'))) {
            $offset = number_format(stripslashes($this->getVar('offset')),2,'.','');
        }
        else {
            $error = true;
        }

        // Checking that orientation exists and is containing only digits, and is >=0 and <=359
        // Then converting the STG orientation into the future DB (true) orientation and with correct decimal format.
        if (\FormChecker::isHeading($this->getVar('heading'))) {
            $heading = number_format(stripslashes($this->getVar('heading')),1,'.','');
        }
        else {
            $error = true;
        }
        
        // Checking that comment exists. Just a small verification as it's not going into DB.
        $inputComment = $this->getVar('comment');
        if ($inputComment != '' && \FormChecker::isComment($inputComment)) {
            $sent_comment = stripslashes($inputComment);
        }
        else {
            $error = true;
        }
        
        // Checking that email is valid (if it exists).
        //(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
        $failed_mail = false;
        $inputEmail = $this->getVar('email');
        if (\FormChecker::isEmail($inputEmail)) {
            $safe_email = htmlentities(stripslashes($inputEmail));
        }
        else {
            $failed_mail = true;
        }
        
        // If there is no error, insert the object to the pending requests table.
        if (!$error) {
            $objectFactory = new \ObjectFactory($this->objectDaoRO);
            $newObject = $objectFactory->createObject(-1, $model_id, $long, $lat, $ob_country, 
            $offset, \ObjectUtils::headingSTG2True($heading), 1, $modelMD->getName());
    
            // Detect if the object is already in the database
            if ($this->objectDaoRO->checkObjectAlreadyExists($newObject)) {
                $objectExists = true;
                include 'view/submission/check_add.php';
                return;
            }

            $newObjects = array();
            $newObjects[] = $newObject;
            $request = new \model\RequestMassiveObjectsAdd();
            $request->setNewObjects($newObjects);
            $request->setContributorEmail($safe_email);
            $request->setComment($sent_comment);
            
            
            try {
                $updatedReq = $requestDaoRW->saveRequest($request);
            } catch (Exception $e) {
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

            $emailSubmit = \EmailContentFactory::getObjectAddRequestPendingEmailContent($dtg, $ipaddr, $host, $modelMD, $updatedReq);
            $emailSubmit->sendEmail("", true);

            // Mailing the submitter to tell him that his submission has been sent for validation
            if (!$failed_mail) {
                $emailSubmit = \EmailContentFactory::getObjectAddRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $updatedReq, $modelMD);
                $emailSubmit->sendEmail($safe_email, false);
            }
        }

        include 'view/submission/check_add.php';
    }
}
