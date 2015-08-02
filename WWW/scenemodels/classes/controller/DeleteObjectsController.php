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
 * Controller for objects deletions requests.
 *
 * @author Julien Nguyen
 */
class DeleteObjectsController extends ControllerMenu {
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
        
        $countObjs = number_format($this->objectDaoRO->countObjects(), '0', '', ' ');
        
        include 'view/submission/find_obj_delete_form.php';
    }
    
    /**
     * Displays the find results action
     */
    public function findObjWithPosAction() {
        parent::menu();
        
        $error_text = "";
        $error = false;

        $lat = number_format(stripslashes($_POST['latitude']),7,'.','');
        $long = number_format(stripslashes($_POST['longitude']),7,'.','');
        
        // Checking that latitude exists, is of good length and is containing only digits, - or ., is >=-90 and <=90 and with correct decimal format.
        if (!\FormChecker::isLatitude($lat)) {
            $error_text .= "Latitude mismatch!<br/>";
            $error = true;
        }

        // Checking that longitude exists, if of good length and is containing only digits, - or ., is >=-180 and <=180 and with correct decimal format.
        if (!\FormChecker::isLongitude($long)) {
            $error_text .= "Longitude mismatch!<br/>";
            $error = true;
        }
        
        if ($error) {
            $page_title = "Automated Objects Deletion Form";
            // $error_text is defined above
            include 'view/error_page.php';
            return;
        }
        
        // Let's see in the database if something exists at this position
        $candidateObjects = $this->objectDaoRO->getObjectsAt($long, $lat);

        // We have no result
        if (count($candidateObjects) == 0) {
            $page_title = "Automated Objects Deletion Form";
            $error_text = "Sorry, but no object was found at position longitude: ".$long.", latitude: ".$lat.". Please <a href='javascript:history.go(-1)'>go back and check your position</a> (see in the relevant STG file).";
            include 'view/error_page.php';
            return;
        }
        
        $candidateModelMDs = array();
        $objGroups = array();
        foreach ($candidateObjects as $candidateObj) {
            $modelId = $candidateObj->getModelId();
            $candidateModelMDs[$modelId] = $this->getModelDaoRO()->getModelMetadata($modelId);
            
            $groupId = $candidateObj->getGroupId();
            $objGroups[$groupId] = $this->objectDaoRO->getObjectsGroup($groupId);
        }
        
        include 'view/submission/select_obj_delete_form.php';
    }
    
    /**
     * Display a confirmation page for the deletion of the given object
     */
    public function confirmDeleteFormAction() {
        $objToDelId = $this->getVar('delete_choice');
        if (!\FormChecker::isObjectId($objToDelId)) {
            $page_title = "Automated Objects Deletion Form";
            $error_text = "Sorry, but the object you want to delete does not have a valid ID.";
            include 'view/error_page.php';
            return;
        }
        
        // Let's grab the information about this object from the database
        try {
            $objectToDel = $this->objectDaoRO->getObject($objToDelId);
        } catch (Exception $e) {
            $page_title = 'Automated Objects Deletion Form';
            $error_text = 'Sorry, but no object with id '.$objToDelId.' was found.';
            include 'view/error_page.php';
            return;
        }
        
        $modelMDToDel = $this->getModelDaoRO()->getModelMetadata($objectToDel->getModelId());
        $objGroup = $this->objectDaoRO->getObjectsGroup($objectToDel->getGroupId());
        include 'view/submission/confirm_obj_deletion_form.php';
    }
    
    /**
     * Requests for deletion action
     */
    public function requestForDeleteAction() {
        parent::menu();
        $resp = parent::checkCaptcha();
        // What happens when the CAPTCHA was entered incorrectly
        if (!$resp->is_valid) {
            $page_title = "Automated Objects Deletion Form";
            $error_text = "Sorry but the reCAPTCHA wasn't entered correctly. <a href='javascript:history.go(-1)'>Go back and try it again</a>" .
                 "<br />(reCAPTCHA complained: " . $resp->error . ")<br />" .
                 "Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
            include 'view/error_page.php';
        }
    
        $objToDelId = $this->getVar('delete_choice');
        if (!\FormChecker::isObjectId($objToDelId)) {
            $page_title = "Automated Objects Deletion Form";
            $error_text = "Sorry, but the object you want to delete does not have a valid ID.";
            include 'view/error_page.php';
            return;
        }
        
        $errors = array();

        $safe_email = null;
        $inputEmail = $this->getVar('email');
        if ($inputEmail != null) {
            if (\FormChecker::isEmail($inputEmail)) {
                $safe_email = htmlentities(stripslashes($this->getVar('email')));
            } else {
                $errors[] = new \Exception('Email mismatch!');
            }
        }
        
        $inputComment = $this->getVar('comment');
        if ($inputComment != null && \FormChecker::isComment($inputComment)) {
            $comment = $inputComment;
        } else {
            $comment = "";
        }
        
        // Final step to edition
        if (count($errors) == 0) {
            $objectToDel = $this->objectDaoRO->getObject($objToDelId);
            $modelMD = $this->getModelDaoRO()->getModelMetadata($objectToDel->getModelId());

            $request = new \model\RequestObjectDelete();
            $request->setObjectToDelete($objectToDel);
            $request->setComment($comment);
            $request->setContributorEmail($safe_email);

            try {
                $requestDaoRW = \dao\DAOFactory::getInstance()->getRequestDaoRW();
                $updatedReq = $requestDaoRW->saveRequest($request);
            } catch (Exception $ex) {
                echo "<p class=\"center\">Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.</p><br />";
                include 'view/footer.php';
                exit;
            }

            // Sending mail if there is no false and SQL was correctly inserted.
            // Sets the time to UTC.
            date_default_timezone_set('UTC');
            $dtg = date('l jS \of F Y h:i:s A');

            // Retrieving the IP address of the submitter (takes some time to resolve the IP address though).
            $ipaddr = stripslashes($_SERVER['REMOTE_ADDR']);
            $host   = gethostbyaddr($ipaddr);

            $emailSubmit = \EmailContentFactory::getObjectDeleteRequestPendingEmailContent($dtg, $ipaddr, $host, $modelMD, $updatedReq);
            $emailSubmit->sendEmail("", true);

            // Mailing the submitter and tell him that his submission has been sent for validation.
            if (!$safe_email) {
                $emailSubmit = \EmailContentFactory::getObjectDeleteRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $updatedReq, $modelMD);
                $emailSubmit->sendEmail($safe_email, false);
            }
            
            include 'view/submission/check_delete.php';
        }
    }
}
