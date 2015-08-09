<?php

// Inserting libs
require_once '../autoload.php';
$requestDaoRO = \dao\DAOFactory::getInstance()->getRequestDaoRO();

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
    case "model\RequestObjectUpdate":
        $pending_requests .= "This is an object update request!\n";
        $pending_requests .= "http://".$_SERVER['SERVER_NAME']."/submission/object/submission.php?action=check&sig=".$sig."\n";
        break;

    case "model\RequestObjectDelete":
        $pending_requests .= "This is an object deletion request!\n";
        $pending_requests .= "http://".$_SERVER['SERVER_NAME']."/submission/object/submission.php?action=check&sig=".$sig."\n";
        break;

    case "model\RequestMassiveObjectsAdd":
        $pending_requests .= "This is an object(s) addition request!\n";
        $pending_requests .= "http://".$_SERVER['SERVER_NAME']."/submission/object/mass_submission.php?action=check&sig=".$sig."\n";
        break;

    case "model\RequestModelAdd":
        $pending_requests .= "This is a 3D model addition request!\n";
        $pending_requests .= "http://".$_SERVER['SERVER_NAME']."/app.php?c=AddModelValidator&a=viewRequest&sig=".$sig."\n";
        break;

    case "model\RequestModelUpdate":
        $pending_requests .= "This is a 3D model update request!\n";
        $pending_requests .= "http://".$_SERVER['SERVER_NAME']."/app.php?c=UpdateModelValidator&a=viewRequest&sig=".$sig."\n";
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