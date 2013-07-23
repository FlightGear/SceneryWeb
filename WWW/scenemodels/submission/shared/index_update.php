<?php

// Inserting libs
require_once '../../inc/functions.inc.php';

// Checking DB availability before all
$ok = check_availability();

if (!$ok) {
    $page_title = "Automated Objects Update Form";
    $error_text = "Sorry, but the database is currently unavailable. " .
                  "We are doing the best to put it back up online. ".
                  "Please come back again soon.";
    include '../../inc/error_page.php';
    exit;
}

$page_title = "Automated Objects Update Form";
require '../../inc/header.php';
?>

<script src="/inc/js/check_form.js" type="text/javascript"></script>
<script type="text/javascript">
/*<![CDATA[*/
function validateForm()
{
    var form = document.getElementById("edition");

    if (!checkStringNotDefault(form["longitude"], "")
        || !checkNumeric(form["longitude"],-180,180) ||
        !checkStringNotDefault(form["latitude"], "")
        || !checkNumeric(form["latitude"],-90,90))
        return false;
}
/*]]>*/
</script>

<h1>Objects Automated Update Form</h1>

<p>
    Through this form you can update a shared object (eg. windturbine, power pylon) at a given location. You can alternatively look for the object on <a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/coverage.php">the map</a> if you are unsure of the exact coordinates of the object.
</p>

<form id="edition" method="post" action="check_update_shared.php" onsubmit="return validateForm();">
<table>
    <tr>
        <td><label for="longitude">Longitude<em>*</em><span>This is the WGS84 longitude of the object. Has to be between -180 and 180.</span></label></td>
        <td>
            <input type="text" name="longitude" id="longitude" maxlength="13" value="0" onkeyup="checkNumeric(this,-180,180);" />
        </td>
    </tr>
    <tr>
        <td><label for="latitude">Latitude<em>*</em><span>This is the WGS84 latitude of the object. Has to be between -90 and 90.</span></label></td>
        <td>
            <input type="text" name="latitude" id="latitude" maxlength="13" value="0" onkeyup="checkNumeric(this,-90,90);" />
        </td>
    </tr>
    <tr>
        <td colspan="2" class="submit">
            <input name="IPAddr" type="hidden" value="<?php echo $_SERVER['REMOTE_ADDR']?>" />
            <input type="submit" value="Check for objects at this position" />
        </td>
    </tr>
</table>
</form>

<?php require '../../inc/footer.php'; ?>
