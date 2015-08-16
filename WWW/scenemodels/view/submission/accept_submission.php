<?php
$pageTitle = "Automated Submission Form";

include 'view/header.php';
echo "<p class=\"center\">Now processing request #".$request->getId().".</p>";
echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";
echo "<p class=\"center ok\">Pending entries correctly deleted from the pending request table.</p>";
include 'view/footer.php';
?>