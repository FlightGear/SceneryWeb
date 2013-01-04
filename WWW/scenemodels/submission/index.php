<?php
	$page_title = "Automated Scenery Submission Forms";
	include '../inc/header.php';
?>

<h1>FlightGear scenery objects and models submission</h1>

<p>
    Please read <a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/contribute.php">this page</a> in order to understand what items those forms are looking for. All submissions are being followed and logged, so <b>DO NOT TAKE THIS</b> as a sandbox.
</p>

<table>
    <tr align="left">
        <td align="left">
            Now select the operation you would like to perform:
            <ul>
                <li>on objects (eg windturbines, pylons, generic buildings...):</li>
                <ul>
                    <li><a href="shared/index.php">adding objects</a>.</li>
                    <li><a href="shared/index_delete.php">deleting objects</a> (delete an existing shared object).</li>
                    <li><a href="shared/index_update.php">updating objects</a> (updating position, offset of an object...).</li>
                    <li><a href="shared/index_mass_import.php">massive import of objects</a> (adding tens of lines of objects in one click).</li>
                </ul>
                <li>on 3D models (models designed for a specific location, eg Eiffel Tower):</li>
                <ul>
                    <li><a href="static/index.php">adding a new static or shared 3D model</a> (adding a static or shared 3D model to the database).</li>
                </ul>
            </ul>
            Comments or contributions propositions are always welcome through the usual channels (<a href="http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel">devel list</a>, <a href="http://www.flightgear.org/forums/viewtopic.php?f=5&amp;t=14671">forum</a>).
        </td>
    </tr>
</table>

<?php include '../inc/footer.php'; ?>
