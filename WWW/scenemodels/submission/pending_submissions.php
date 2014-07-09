<?php

// Inserting libs
require_once '../classes/EmailContentFactory.php';
require_once '../classes/DAOFactory.php';
$requestDaoRO = DAOFactory::getInstance()->getRequestDaoRO();

// Get pending requests
$requests = $requestDaoRO->getPendingRequests();

// Talking back to submitter.
// Declare variables
$pending_requests = "";

// List all requests
foreach ($requests as $request) {
    $sig = $request->getSig();
    $pending_requests .= "\nRequest #".$request->getId()."\n";
    $pending_requests .= "=========================================================================================\n";

    switch (get_class($request)) {
    case "RequestObjectAdd":
        $pending_requests .= "Object addition request!\n".
                             "http://".$_SERVER['SERVER_NAME']."/submission/object/submission.php?action=check&sig=".$sig."\n";
        break;
    case "RequestObjectUpdate":
        $pending_requests .= "This is an object update request!\n";
        $pending_requests .= "http://".$_SERVER['SERVER_NAME']."/submission/object/submission.php?action=check&sig=".$sig."\n";
        break;

    case "RequestObjectDelete":
        $pending_requests .= "This is an object deletion request!\n";
        $pending_requests .= "http://".$_SERVER['SERVER_NAME']."/submission/object/submission.php?action=check&sig=".$sig."\n";
        break;

    case "RequestMassiveObjectsAdd":
        $pending_requests .= "This is a massive objects addition request!\n";
        $pending_requests .= "http://".$_SERVER['SERVER_NAME']."/submission/object/mass_submission.php?action=check&sig=".$sig."\n";
        break;

    case "RequestModelAdd":
        $pending_requests .= "This is a 3D model addition request!\n";
        $pending_requests .= "http://".$_SERVER['SERVER_NAME']."/submission/model/model_add_submission.php?mo_sig=".$sig."\n";
        break;

    case "RequestModelUpdate":
        $pending_requests .= "This is a 3D model update request!\n";
        $pending_requests .= "http://".$_SERVER['SERVER_NAME']."/submission/model/model_update_submission.php?mo_sig=".$sig."\n";
        break;
    }
}

// Sets the time to UTC.
date_default_timezone_set('UTC');
$dtg = date('l jS \of F Y h:i:s A');

if (count($requests) > 0) {
    $emailSubmit = EmailContentFactory::getPendingRequestsEmailContent($pending_requests);  
} else {
    $emailSubmit = EmailContentFactory::getPendingRequestsNoneEmailContent();
}
$emailSubmit->sendEmail("", true);

?>