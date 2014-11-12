<?php
require_once 'EmailContent.php';

/*
 * Copyright (C) 2014 - FlightGear Team
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
 * Description of EmailContentFactory
 *
 * @author Julien Nguyen <julien.nguyen3@gmail.com>
 */
class EmailContentFactory {

    static private function format($bodyMessage) {
        $message = "Hi,\r\n\r\n".
                   $bodyMessage.
                   "Sincerely,\r\n\r\n" .
                   "FlightGear Scenery Team\r\n\r\n" .
                   "-----------------\r\n" .
                   "This process has gone through antispam measures. However, if this email is not sollicited, please excuse us and report at http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671";
        
        return wordwrap($message, 70, "\r\n");
    }
    
    static public function getObjectsAddRequestAcceptedEmailContent($dtg, $request, $comment) {
        $subject = "Object(s) import accepted";
        $message = "On ".$dtg." UTC, you issued an object(s) import request.\r\n\r\n" .
                   "We are glad to let you know that this request has been accepted!\r\n\r\n" .
                   "For reference, the ID of this request was '".$request->getId(). "'\r\n\r\n";
        if (!empty($comment)) {
            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
        }
        $message .= "The corresponding entries will be added in TerraSync at " . check_terrasync_update_passed() . ". You can follow TerraSync's data update at the following url: http://code.google.com/p/terrascenery/source/list\r\n\r\n" .
                    "Thanks for your help in making FlightGear better!\r\n\r\n";
        
        return new EmailContent($subject, self::format($message));
    }
    
    
    static public function getMassImportRequestPendingEmailContent($dtg, $ipaddr, $host, $request) {
        $subject = "Massive object import needs validation";
        $message = "We would like to let you know that a new objects massive import request is pending. " .
                   "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
        $contrEmail = $request->getContributorEmail();
        if (!empty($contrEmail)) {
            $message .= "and with email address ".$contrEmail." ";
        }
        $message .= "issued an objects massive import request (#".$request->getId().").\r\n\r\n" .
                    "Comment by user: ".strip_tags($request->getComment())."\r\n\r\n" .
                    "Now please click the following link to check and confirm ".
                    "or reject the submission: http://".$_SERVER['SERVER_NAME']."/submission/object/mass_submission.php?action=check&sig=". $request->getSig() ."&email=". $contrEmail . "\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
    
    static public function getMassImportRequestRejectedEmailContent($dtg, $request, $comment) {
        $subject = "Massive object import rejected";
        $message = "On ".$dtg." UTC, you issued an objects massive import request.\r\n\r\n" .
                   "We are sorry to let you know that this request has been rejected.\r\n\r\n" .
                   "For reference, the ID of this request was '".$request->getId(). "'\r\n\r\n";
        if (!empty($comment)) {
            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
        }
        $message .= "Please do not let this stop you from sending us corrected object locations or models.\r\n\r\n";
        
        return new EmailContent($subject, self::format($message));
    }
    
    static public function getMassImportSentForValidationEmailContent($ipaddr, $host, $dtg, $request) {
        $subject = "Massive object import";
        $message = "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a mass submission request.\r\n\r\n" .
                   "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed.\r\n\r\n" .
                   "For reference, the ID of this request is '".$request->getId(). "'\r\n\r\n";
        return new EmailContent($subject, self::format($message));
    }
    
    static public function getModelUpdateRequestAcceptedEmailContent($dtg, $request, $comment) {
        $modelMD = $request->getNewModel()->getMetadata();
        $subject = "3D model update accepted";
        $message = "On ".$dtg." UTC, you issued a 3D model update request.\r\n\r\n" .
                   "We are glad to let you know that this request has been accepted!\r\n\r\n" .
                   "For reference, the ID of this request is '".$request->getId(). "' and it is named '". $modelMD->getName() ."'.\r\n\r\n";
        if (!empty($comment)) {
            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
        }
        $message .= "The corresponding entries will be updated in TerraSync at " .
                check_terrasync_update_passed() . ". You can follow TerraSync's data update at the following url: http://code.google.com/p/terrascenery/source/list ".
                "and check the model at http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$modelMD->getId()."\r\n\r\n" .
                "Thanks for your help in making FlightGear better!\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
    
    static public function getModelUpdateRequestPendingEmailContent($dtg, $ipaddr, $host, $request) {
        $newModelMD = $request->getNewModel()->getMetadata();
        $safeContrEmail = $request->getContributorEmail();
        
        $subject = "3D model update needs validation.";
        $message = "We would like to let you know that an update for a 3D model request is pending (#".$request->getId()."). " .
                   "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
        if (!empty($safeContrEmail)) {
            $message .= "and with email address ".$safeContrEmail." ";
        }
        $message .= "issued the following request:\r\n\r\n" .
                    "Family:           ". $newModelMD->getModelsGroup()->getName() . "\r\n[ http://".$_SERVER['SERVER_NAME']."/modelbrowser.php?shared=".$newModelMD->getModelsGroup()->getId()." ]\r\n" .
                    "Path:             ". $newModelMD->getFilename() . "\r\n" .
                    "Author:           ". $newModelMD->getAuthor()->getName() ."\r\n" .
                    "Contributor email ". $safeContrEmail ."\r\n" .
                    "Model name:       ". $newModelMD->getName() ."\r\n" .
                    "Description:      ". strip_tags($newModelMD->getDescription()) ."\r\n" .
                    "Comment by user:  ". strip_tags($request->getComment()) . "\r\n\r\n" .
                    "Now please click the following link to view and confirm/reject the submission: " . "http://".$_SERVER['SERVER_NAME']."/submission/model/model_update_submission.php?mo_sig=". $request->getSig() ."&email=". $safeContrEmail . "\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
    
    static public function getModelUpdateRequestRejectedEmailContent($dtg, $request, $comment) {
        $newModelMD = $request->getNewModel()->getMetadata();
        
        $subject = "3D model update rejected";
        $message = "On ".$dtg." UTC, you issued a 3D model update request.\r\n\r\n" .
                   "We are sorry to let you know that this request has been rejected.\r\n\r\n" .
                   "For reference, the ID of this request was '".$request->getId(). "' and it was named '". $newModelMD->getName() ."'.\r\n\r\n";
        if (!empty($comment)) {
            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
        }
        $message .=  "Please do not let this stop you from sending us an improved version of this model or other models.\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
    
    static public function getModelUpdateRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $request) {
        $newModelMD = $request->getNewModel()->getMetadata();
        $subject = "3D model update request";
        $message = "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a 3D model update request.\r\n\r\n" .
                   "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed.\r\n\r\n" .
                   "For reference, the ID of this request is '".$request->getId(). "'\r\n\r\n" .
                   "Family:           ". $newModelMD->getModelsGroup()->getName() . "\r\n" . "[ http://".$_SERVER['SERVER_NAME']."/modelbrowser.php?shared=".$newModelMD->getModelsGroup()->getId()." ]\r\n" .
                   "Path:             ". $newModelMD->getFilename() . "\r\n" .
                   "Author:           ". $newModelMD->getAuthor()->getName() ."\r\n" .
                   "Contributor email ". $request->getContributorEmail() ."\r\n" .
                   "Model name:       ". $newModelMD->getName() ."\r\n" .
                   "Description:      ". strip_tags($newModelMD->getDescription()) ."\r\n" .
                   "Comment by user:  ". strip_tags($request->getComment()) . "\r\n\r\n";
        return new EmailContent($subject, self::format($message));
    }
    
    static public function getModelUpdateRequestSentForValidationAuthorEmailContent($dtg, $ipaddr, $host, $request) {
        $newModelMD = $request->getNewModel()->getMetadata();
        
        $subject = "3D model update request";
        $message = "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), issued a 3D model update request for your model.\r\n\r\n" .
                   "We would like to let you know that this request has been sent for validation.\r\n\r\n" .
                   "For reference, the ID of this request is '".$request->getId(). "'\r\n\r\n" .
                   "Family:           ". $newModelMD->getModelsGroup()->getName() . "\r\n" . "[ http://".$_SERVER['SERVER_NAME']."/modelbrowser.php?shared=".$newModelMD->getModelsGroup()->getId()." ]\r\n" .
                   "Path:             ". $newModelMD->getFilename() . "\r\n" .
                   "Author:           ". $newModelMD->getAuthor()->getName() ."\r\n" .
                   "Contributor email ". $request->getContributorEmail() ."\r\n" .
                   "Model name:       ". $newModelMD->getName() ."\r\n" .
                   "Description:      ". strip_tags($newModelMD->getDescription()) ."\r\n" .
                   "Comment by user:  ". strip_tags($request->getComment()) . "\r\n\r\n";
            
        return new EmailContent($subject, self::format($message));
    }
    
    static public function getObjectRequestAcceptedEmailContent($request, $comment) {
        $subject = "Object request accepted";
        $message = "We would like to let you know that the object (addition, update, deletion) request #".$request->getId(). " was successfully treated in the fgs_objects table. The corresponding pending entry has consequently been deleted from the pending requests table.\r\n\r\n";
        if (!empty($comment)) {
            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
        }
        $message .= "The corresponding entries will be deleted, added or updated in TerraSync at " . check_terrasync_update_passed() . ". You can follow TerraSync's data update at the following url: http://code.google.com/p/terrascenery/source/list\r\n\r\n" .
                    "Please don't forget to use the massive import form rather than the single one if you have many objects to add!\r\n\r\n";
            
        return new EmailContent($subject, self::format($message));
    }
    
    static public function getPendingRequestsEmailContent($pendingRequests) {
        $subject = "Pending requests";
        $message = "We would like to give you an overview of the remaining pending requests.\r\n\r\n" .
                   $pendingRequests . "\r\n" .
                   "They should be somewhere in your mails. Please check again.\r\n\r\n";
            
        return new EmailContent($subject, self::format($message));
    }
    
    static public function getPendingRequestsNoneEmailContent() {
        $subject = "Pending requests";
        $message = "There are currently no pending requests. Well done! Hopefully some more will come soon ;-).\r\n\r\n";
            
        return new EmailContent($subject, self::format($message));
    }
    
    static public function getObjectRejectedEmailContent($request, $comment) {
        $subject = "Object request rejected";
        $message = "We are sorry to let you know that the object request #".$request->getId(). " was rejected.\r\n\r\n";
        if (!empty($comment)) {
            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
        }
        
        return new EmailContent($subject, self::format($message));
    }
    
    static public function getObjectDeleteRequestPendingEmailContent($dtg, $ipaddr, $host, $modelMD, $request) {
        $safeEmail = $request->getContributorEmail();
        $objectToDel = $request->getObjectToDelete();
        
        $subject = "Object deletion needs validation";
        $message = "We would like to let you know that a new object deletion request is pending (#".$request->getId()."). " .
                   "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
        if (!empty($safeEmail)) {
            $message .= "and with email address ".$safeEmail." ";
        }
        $message .= "issued the following request:\r\n\r\n" .
                    "Family:           " .$modelMD->getModelsGroup()->getName(). "\r\n" .
                    "Model:            " .$modelMD->getName(). "\r\n" .
                    "Ob. text/metadata:" .$objectToDel->getDescription(). "\r\n" .
                    "Latitude:         " .$objectToDel->getLatitude(). "\r\n" .
                    "Longitude:        " .$objectToDel->getLongitude(). "\r\n" .
                    "Ground elevation: " .$objectToDel->getGroundElevation(). "\r\n" .
                    "Elevation offset: " .$objectToDel->getElevationOffset(). "\r\n" .
                    "True orientation: " .$objectToDel->getOrientation(). "\r\n" .
                    "Comment:          " .strip_tags($request->getComment()) . "\r\n" .
                    "Map:              http://mapserver.flightgear.org/popmap/?lon=". $objectToDel->getLongitude() ."&lat=". $objectToDel->getLatitude() ."&zoom=14\r\n\r\n" .
                    "Now please click the following link to view and confirm/reject the submission: http://".$_SERVER['SERVER_NAME']."/submission/object/submission.php?action=check&sig=". $request->getSig() . "&email=" . $safeEmail . "\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
    
    static public function getObjectDeleteRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $request, $modelMD) {
        $objectToDel = $request->getObjectToDelete();
        
        $subject = "Object deletion";
        $message = "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued an object deletion request.\r\n\r\n" .
                   "We would like to let you know that this request was sent for validation. Allow up to a few days for your request to be processed.\r\n\r\n" .
                   "For reference, the ID of this request is '".$request->getId(). "'\r\n\r\n" .
                   "Family:           " .$modelMD->getModelsGroup()->getName(). "\r\n" .
                   "Model:            " .$modelMD->getName(). "\r\n" .
                   "Latitude:         " .$objectToDel->getLatitude(). "\r\n" .
                   "Longitude:        " .$objectToDel->getLongitude(). "\r\n" .
                   "Ground elevation: " .$objectToDel->getGroundElevation(). "\r\n" .
                   "Elevation offset: " .$objectToDel->getElevationOffset(). "\r\n" .
                   "True orientation: " .$objectToDel->getOrientation(). "\r\n" .
                   "Comment:          " .strip_tags($request->getComment()) . "\r\n".
                   "Map:              http://mapserver.flightgear.org/popmap/?lon=". $objectToDel->getLongitude() ."&lat=". $objectToDel->getLatitude() ."&zoom=14\r\n\r\n";
        return new EmailContent($subject, self::format($message));
    }
    
    static public function getObjectAddRequestPendingEmailContent($dtg, $ipaddr, $host, $modelMD, $request) {
        $safeEmail = $request->getContributorEmail();
        $newObjects = $request->getNewObjects();
        $newObject = $newObjects[0];
        
        $subject = "Object addition needs validation";
        $message = "We would like to let you know that a new object request is pending (#".$request->getId()."). " .
                   "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
        if (!empty($safeEmail)) {
            $message .= "and with email address ".$safeEmail." ";
        }
        $message .= "issued the following request:\r\n\r\n" .
                    "Family:           ". $modelMD->getModelsGroup()->getName() . "\r\n" . "[ http://".$_SERVER['SERVER_NAME']."/modelbrowser.php?shared=".$modelMD->getModelsGroup()->getId()." ]" . "\r\n" .
                    "Model:            ". $modelMD->getName() . "\r\n" . "[ http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$modelMD->getId()." ]" . "\r\n" .
                    "Latitude:         ". $newObject->getLatitude() . "\r\n" .
                    "Longitude:        ". $newObject->getLongitude() . "\r\n" .
                    "Country:          ". $newObject->getCountry()->getName() . "\r\n" .
                    "Ground elevation will be automagically computed\r\n" .
                    "Elevation offset: ". $newObject->getElevationOffset() . "\r\n" .
                    "True orientation: ". $newObject->getOrientation() . "\r\n" .
                    "Comment:          ". strip_tags($request->getComment()) . "\r\n" .
                    "Map:              http://mapserver.flightgear.org/popmap/?lon=". $newObject->getLongitude() ."&lat=". $newObject->getLatitude() ."&zoom=14\r\n\r\n" .
                    "Now please click the following link to view and confirm/reject the submission: " . "http://".$_SERVER['SERVER_NAME']."/submission/object/mass_submission.php?action=check&sig=". $request->getSig() ."&email=". $safeEmail."\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
    
    static public function getObjectAddRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $request, $modelMD) {
        $newObjects = $request->getNewObjects();
        $newObject = $newObjects[0];
        
        $subject = "Object addition";
        $message = "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued an object addition request.\r\n\r\n" .
                   "We would like to let you know that this request was sent for validation. Allow up to a few days for your request to be processed.\r\n\r\n" .
                   "For reference, the ID of this request is '".$request->getId(). "'\r\n\r\n" .
                   "Family:           ". $modelMD->getModelsGroup()->getName() . "\r\n" .
                   "Model:            ". $modelMD->getName() . "\r\n" .
                   "Latitude:         ". $newObject->getLatitude() . "\r\n" .
                   "Longitude:        ". $newObject->getLongitude() . "\r\n" .
                   "Country:          ". $newObject->getCountry()->getName() . "\r\n" .
                   "Elevation offset: ". $newObject->getElevationOffset() . "\r\n" .
                   "True orientation: ". $newObject->getOrientation() . "\r\n" .
                   "Comment:          ". strip_tags($request->getComment()) ."\r\n\r\n" .
                   "Please remember to use the massive insertion script should you have many objects to add: ".
                   "http://".$_SERVER['SERVER_NAME']."/submission/object/index_mass_import.php\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
    
    static public function getObjectUpdateRequestPendingEmailContent($dtg, $ipaddr, $host, $oldModelMD, $newModelMD, $request) {
        $safeEmail = $request->getContributorEmail();
        $oldObject = $request->getOldObject();
        $newObject = $request->getNewObject();
        
        $subject = "Object update needs validation";
        $message = "We would like to let you know that an object update request is pending (#".$request->getId()."). " .
                   "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
        if (!empty($safeEmail)) {
            $message .= "and with email address ".$safeEmail." ";
        }
        $message .= "issued the following request:\r\n\r\n" .
                    "Object #:          ". $oldObject->getId()."\r\n" .
                    "Family:            ". $oldModelMD->getModelsGroup()->getName() ." => ".$newModelMD->getModelsGroup()->getName()."\r\n" .
                    "[ http://".$_SERVER['SERVER_NAME']."/modelbrowser.php?shared=".$newModelMD->getModelsGroup()->getId()." ]" . "\r\n" .
                    "Model:             ". $oldModelMD->getName() ." => ".$newModelMD->getName()."\r\n" .
                    "[ http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$newModelMD->getId()." ]" . "\r\n" .
                    "Latitude:          ". $oldObject->getLatitude() . "  => ".$newObject->getLatitude()."\r\n" .
                    "Longitude:         ". $oldObject->getLongitude() . " => ".$newObject->getLongitude()."\r\n" .
                    "Ground elevation:  ". $oldObject->getGroundElevation() . " => ".$newObject->getGroundElevation()."\r\n" .
                    "Elevation offset:  ". $oldObject->getElevationOffset() . " => ".$newObject->getElevationOffset()."\r\n" .
                    "True orientation:  ". $oldObject->getOrientation() . " => ".$newObject->getOrientation()."\r\n" .
                    "Map (new position): http://mapserver.flightgear.org/popmap/?lon=". $newObject->getLongitude() ."&lat=". $newObject->getLatitude() ."&zoom=14" . "\r\n" .
                    "Comment:           ". strip_tags($request->getComment()) ."\r\n\r\n" .
                    "Now please click the following link to view and confirm/reject the submission: http://".$_SERVER['SERVER_NAME']."/submission/object/submission.php?action=check&sig=". $request->getSig() . "&email=" . $safeEmail . "\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
    
    static public function getObjectUpdateRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $request, $oldModelMD, $newModelMD) {
        $oldObject = $request->getOldObject();
        $newObject = $request->getNewObject();
        
        $subject = "Object update";
        $message = "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued an object update request." . "\r\n\r\n" .
                   "We would like to let you know that this request was sent for validation. Allow up to a few days for your request to be processed." . "\r\n\r\n" .
                   "For reference, the ID of this request is '".$request->getId(). "'\r\n\r\n" .
                   "Object #:          ".$oldObject->getId()."\r\n" .
                   "Family:            ". $oldModelMD->getModelsGroup()->getName() ." => ".$newModelMD->getModelsGroup()->getName()."\r\n" .
                   "[ http://".$_SERVER['SERVER_NAME']."/modelbrowser.php?shared=".$newModelMD->getModelsGroup()->getId()." ]\r\n" .
                   "Model:             ". $oldModelMD->getName() ." => ".$newModelMD->getName()."\r\n" .
                   "[ http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$newModelMD->getId()." ]\r\n" .
                   "Latitude:          ". $oldObject->getLatitude() . "  => ".$newObject->getLatitude()."\r\n" .
                   "Longitude:         ". $oldObject->getLongitude() . " => ".$newObject->getLongitude()."\r\n" .
                   "Ground elevation:  ". $oldObject->getGroundElevation() . " => will be recomputed\r\n" .
                   "Elevation offset:  ". $oldObject->getElevationOffset() . " => ".$newObject->getElevationOffset()."\r\n" .
                   "True rientation:   ". $oldObject->getOrientation() . " => ".$newObject->getOrientation()."\r\n" .
                   "Comment:           ". strip_tags($request->getComment()) ."\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
    
    static public function getAddModelRequestAcceptedEmailContent($dtg, $request, $comment) {
        $newModelMD = $request->getNewModel()->getMetadata();
        
        $subject = "3D model import accepted";
        $message = "On ".$dtg." UTC, you issued a 3D model import request.\r\n\r\n" .
                   "We are glad to let you know that this request was accepted!\r\n\r\n" .
                   "For reference, the ID of this request is '".$request->getId(). "' (model and object) and it is named '". $newModelMD->getName() ."'.\r\n\r\n";
        if (!empty($comment)) {
            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
        }
        $message .= "The corresponding entries will be added in TerraSync at " . check_terrasync_update_passed() . ". You can follow TerraSync's data update at the following url: http://code.google.com/p/terrascenery/source/list and check the model at http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$newModelMD->getId()."\r\n\r\n" .
                    "Thanks for your help in making FlightGear better!\r\n\r\n";
            
        return new EmailContent($subject, self::format($message));
    }
    
    static public function getAddModelRequestPendingEmailContent($dtg, $ipaddr, $host, $request) {
        $contrEmail = $request->getContributorEmail();
        $newModelMD = $request->getNewModel()->getMetadata();
        $newObject = $request->getNewObject();
        
        $subject = "3D model import needs validation.";
        $message = "We would like to let you know that a new 3D model request is pending (#".$request->getId()."). " .
                   "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
        if (!empty($contrEmail)) {
            $message .= "and with email address ".$contrEmail." ";
        }

        $message .= "issued the following request:" . "\r\n\r\n" .
                    "Family:           ". $newModelMD->getModelsGroup()->getName() . "\r\n" . "[ http://".$_SERVER['SERVER_NAME']."/modelbrowser.php?shared=".$newModelMD->getModelsGroup()->getId()." ]" . "\r\n" .
                    "Path:             ". $newModelMD->getFilename() . "\r\n" .
                    "Author:           ". $newModelMD->getAuthor()->getName() ."\r\n" .
                    "Model name:       ". $newModelMD->getName() ."\r\n" .
                    "Description:      ". strip_tags($newModelMD->getDescription()) ."\r\n" .
                    "Latitude:         ". $newObject->getLatitude() . "\r\n" .
                    "Longitude:        ". $newObject->getLongitude() . "\r\n" .
                    "Country:          ". $newObject->getCountry()->getName() . "\r\n" .
                    "Elevation offset: ". $newObject->getElevationOffset() . "\r\n" .
                    "True orientation: ". $newObject->getOrientation() . "\r\n" .
                    "Map:              http://mapserver.flightgear.org/popmap/?lon=". $newObject->getLongitude() ."&lat=". $newObject->getLatitude() ."&zoom=14\r\n\r\n" .
                    "Now please click the following link to view and confirm/reject the submission: " . "http://".$_SERVER['SERVER_NAME']."/submission/model/model_add_submission.php?mo_sig=". $request->getSig() ."&email=". $contrEmail . "\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
    
    static public function getAddModelRequestRejectedEmailContent($dtg, $request, $comment) {
        $modelMD = $request->getNewModel()->getMetadata();
        
        $subject = "3D model import rejected";
        $message = "On ".$dtg." UTC, you issued a 3D model import request.\r\n\r\n" .
                   "We are sorry to let you know that this request was rejected.\r\n\r\n" .
                   "For reference, the ID of this request was '".$request->getId(). "' (model and object) and it was named '". $modelMD->getName() ."'.\r\n\r\n";
        if (!empty($comment)) {
            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
        }
        $message .=  "Please do not let this stop you from sending us an improved version of this model or other models." . "\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
    
    static public function getAddModelRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $request) {
        $newModelMD = $request->getNewModel()->getMetadata();
        $newObject = $request->getNewObject();
        
        $subject = "3D model import";
        $message = "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a 3D model import request.\r\n\r\n" .
                   "We would like to let you know that this request was sent for validation. Allow up to a few days for your request to be processed.\r\n\r\n" .
                   "For reference, the ID of this request is '".$request->getId(). "' (model and object)\r\n\r\n" .
                   "Family:           ". $newModelMD->getModelsGroup()->getName() . "\r\n" . "[ http://".$_SERVER['SERVER_NAME']."/modelbrowser.php?shared=".$newModelMD->getModelsGroup()->getId()." ]" . "\r\n" .
                   "Path:             ". $newModelMD->getFilename() . "\r\n" .
                   "Author:           ". $newModelMD->getAuthor()->getName() ."\r\n" .
                   "Model name:       ". $newModelMD->getName() ."\r\n" .
                   "Description:      ". strip_tags($newModelMD->getDescription()) ."\r\n" .
                   "Latitude:         ". $newObject->getLatitude() . "\r\n" .
                   "Longitude:        ". $newObject->getLongitude() . "\r\n" .
                   "Country:          ". $newObject->getCountry()->getName() . "\r\n" .
                   "Elevation offset: ". $newObject->getElevationOffset() . "\r\n" .
                   "True orientation: ". heading_stg_to_true($newObject->getOrientation()) . "\r\n" .
                   "Map:              http://mapserver.flightgear.org/popmap/?lon=". $newObject->getLongitude() ."&lat=". $newObject->getLatitude() ."&zoom=14\r\n\r\n";

        return new EmailContent($subject, self::format($message));
    }
}
