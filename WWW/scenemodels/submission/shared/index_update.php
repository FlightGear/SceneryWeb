<?php

// Inserting libs

require_once('../../inc/functions.inc.php');

// Checking DB availability before all

$ok=check_availability();

if(!$ok)
{
    $page_title = "Automated Shared Models Positions Update Form";
    include '../../inc/header.php';
?>
<br /><br />
<center><font color="red">Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.</font></center>
<br /><center>The FlightGear team.</center>
<?php include '../../inc/footer.php'; ?>
<?php
}

else
{
?>
<?php
    $page_title = "Automated Shared Models Positions Update Form";
    include '../../inc/header.php';
?>

<script src="../ajax/check_form.js" type="text/javascript"></script>

<p>
<h1 align="center">Positions Automated Update Form</h1>
<center><b>Foreword:</b> This automated form goal is to ease the update of shared models positions within FG Scenery database. <br />There are currently <?php count_objects(); ?> objects in the database.</center>
<br /><br />
<form name="edition" method="post" action="check_update_shared.php">
<table>
    <tr>
        <td><span title="This is the WGS84 longitude of the object you want to update. Has to be between -180.000000 and +180.000000."><a style="cursor: help; ">Longitude</a></span></td>
        <td>
            <input type="text" name="longitude" maxlength="13" value="0" onblur="checkNumeric(this,-180,180);" />
        </td>
    </tr>
    <tr>
        <td><span title="This is the WGS84 latitude of the object you want to update. Has to be between -90.000000 and +90.000000."><a style="cursor: help; ">Latitude</a></span></td>
        <td>
            <input type="text" name="latitude" maxlength="13" value="0" onblur="checkNumeric(this,-90,90);" />
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]?>" />
            <center>
            <input type="submit" value="Check for objects at this position" />
            </center>
        </td>
    </tr>
</table>
</form>
</p>
<?php include '../../inc/footer.php'; ?>
<?php
}
?>
