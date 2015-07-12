<?php

$page_title = "Automated Objects Submission Form";
require 'view/header.php';

// Display errors if exist
foreach ($errors as $error) {
    echo '<p class="warning">'. $error->getMessage() .'</p>';
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
}

require 'view/footer.php';

?>