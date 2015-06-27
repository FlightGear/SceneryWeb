<?php

$page_title = "Automated Objects Submission Form";
require 'view/header.php';

echo "<center>";

// Checking that model_id exists and is containing only figures and with correct decimal format.
if (isset($modelMD)) {
    echo "<p class=\"ok\">Model Name: ".$modelMD->getName()."</p>";
    echo "<p class=\"ok\">Family Name: ".$modelMD->getModelsGroup()->getName()."</p>";
}
else {
    echo "<p class=\"warning\">Model Name mismatch!</p>";
}

// Checking that latitude exists and is containing only digits, - or ., is >=-90 and <=90 and with correct decimal format.
if (isset($lat)) {
    echo "<p class=\"ok\">Latitude: ".htmlentities($lat)."</p>";
}
else {
    echo "<p class=\"warning\">Latitude mismatch!</p>";
}

// Checking that longitude exists and is containing only digits, - or ., is >=-180 and <=180 and with correct decimal format.
if (isset($long)) {
    echo "<p class=\"ok\">Longitude: ".htmlentities($long)."</p>";
}
else {
    echo "<p class=\"warning\">Longitude mismatch!</p>";
}

// Country.
if (isset($country)) {
    echo "<p class=\"ok\">Country: ".$country->getName()."</p>";
}
else {
    echo "<p class=\"warning\">Country error!</p>";
}

// Checking that offset exists and is containing only digits, - or ., is >=-10000 and <=10000 and with correct decimal format.
if (isset($offset)) {
    echo "<p class=\"ok\">Offset: ".$offset."</p>";
}
else {
    echo "<p class=\"warning\">Offset mismatch!</p>";
}

// Checking that orientation exists and is containing only digits, and is >=0 and <=359
// Then converting the STG orientation into the future DB (true) orientation and with correct decimal format.
if (isset($heading)) {
    echo "<p class=\"ok\">STG Orientation: ".$heading.", DB (true) orientation: ".number_format(\ObjectUtils::headingSTG2True($heading),1,'.','')."</p>";
}
else {
    echo "<p class=\"warning\">Orientation mismatch!</p>";
}

// Checking that comment exists. Just a small verification as it's not going into DB.
if (isset($sent_comment)) {
    echo "<p class=\"ok\">Comment: ".htmlentities($sent_comment)."</p>";
}
else {
    echo "<p class=\"warning\">Comment mismatch!</p>";
}

// Email
if (isset($safe_email)) {
    echo "<p class=\"ok\">Email: ".$safe_email."</p>";
}
else {
    echo "<p class=\"warning\">No email was given (not mandatory) or email mismatch!</p>";
}

if (isset($objectExists) && $objectExists) {
    echo "<p class=\"warning\">The object already exists in the database!</p>";
}
    
if (isset($updatedReq)) {
    echo "<br />Your object request has been successfully queued into the FG scenery database update requests!<br />";
    echo "Unless it's rejected, it should appear in Terrasync within a few days.<br />";
    echo "The FG community would like to thank you for your contribution!<br />";
    echo "Want to submit another object? <a href=\"http://".$_SERVER['SERVER_NAME']."/submission/object/\">Click here to go back to the submission page.</a><br />";
    echo "Please remember to use the <a href=\"http://".$_SERVER['SERVER_NAME']."/app.php?c=AddObjects&a=massiveform\">massive insertion script</a> should you have many objects to add.";
    echo "</center>";
}

require 'view/footer.php';

?>