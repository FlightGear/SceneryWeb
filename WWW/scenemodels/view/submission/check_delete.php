<?php
$page_title = "Automated Objects Deletion Form";
include 'view/header.php';

echo "<p class=\"center ok\">You have asked to delete object #".$objectToDel->getId()."</p>";

if ($safe_email != null) {
    echo "<p class=\"center ok\">Email: ".$safe_email."</p>";
}

echo "<p class=\"center\">Your object has been successfully queued into the deletion requests!<br />";
echo "Unless it's rejected, the object should be dropped in Terrasync within a few days.<br />";
echo "The FG community would like to thank you for your contribution!<br />";
echo "Want to delete or submit another position ?<br /> <a href=\"http://".$_SERVER['SERVER_NAME']."/submission/\">Click here to go back to the submission page.</a></p>";


include 'view/footer.php';