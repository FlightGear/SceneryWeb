<?php
if ((isset($_POST["action"]))) {
    // Inserting libs
    require_once ('../../inc/functions.inc.php');
    $page_title = "Automated Models Submission Form";
    include '../../inc/header.php';

    // Prepare a generic mail

    // If $action=reject
        // - Drop fgs_position_requests;
        // - Send 2 mails

    if($_POST["action"] == "Reject model") {
        echo "Deleting corresponding pending query";
        echo "The user submission has been rejected. Will let user know.";
        echo $_POST["email"];
        echo $_POST["maintainer_comment"];
    }

    // If $action=accept
        // - Execute both requests
        // - Send 2 mails

    if($_POST["action"] == "Submit model") {
        echo "Inserting query into DB";
        echo "Deleting pending queries";
        echo "The user submission has been accepted. Will let user know.";
        echo "You can see the corresponding submission here :";
        echo $_POST["email"];
        echo $_POST["maintainer_comment"];
    }

    include '../../inc/footer.php';
}

else {

// Inserting libs
require_once ('../../inc/functions.inc.php');
include_once '../../inc/geshi/geshi.php';

$page_title = "Automated Models Submission Form";
include '../../inc/header.php';

// Checking DB availability before all
$ok = check_availability();

if(!$ok) {
    $page_title = "Automated Shared Models Positions Pending Requests Form";
    include '../../inc/header.php'; ?>
    <p class="center"><font color="red">Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.</font>
    <br />The FlightGear team.</p>
    <?php include '../../inc/footer.php'; ?>
    <?
}
else {
    // Check the presence of "action", the presence of "signature", its length (64) and its content.
    if ((isset($_GET["sig"])) && ((strlen($_GET["sig"])) == 64) && preg_match("/[0-9a-z]/",$_GET["sig"])) {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if($resource_rw != '0') {

        // Checking the presence of sig into the database
            $result = @pg_query($resource_rw, "select spr_hash, spr_base64_sqlz from fgs_position_requests where spr_hash = '". $_GET["sig"] ."';");
            if (pg_num_rows($result) != 1) {
                $page_title = "Automated Shared Models Positions Pending Requests Form";
                include '../../inc/header.php';
                echo "<center>";
                echo "<font color=\"red\">Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?</font><br />\n";
                echo "Else, please report to fg-devel ML or FG Scenery forum<br />.";
                echo "</center>";
                include '../../inc/footer.php';
                @pg_close($resource_rw);
                exit;
            }
            else {
                   while ($row = pg_fetch_row($result)) {
                        $sqlzbase64 = $row[1];

                        // Base64 decode the query
                        $sqlz = base64_decode($sqlzbase64);

                        // Gzuncompress the query
                        $query_rw = gzuncompress($sqlz);
                        echo $query_rw;
            }
        }
        }
    }
}

echo "<p class=\"center\">Hi, this is the static submission form at http://scenemodels.flightgear.org/submission/static.</p>";
echo "<p class=\"center\">";
echo "<p class=\"center\">The following model has passed all (numerous) verifications by the forementionned script. It should be fine to validate it. However, it's always sane to eye-check it.</p>";
?>
<form name="validation" method="post" action="static_submission.php">
<table>
    <tr>
        <th>Data</th>
        <th>Value</th>
    </tr>
    <tr>
        <td>Author</td>
        <td></td>
    </tr>
    <tr>
        <td>Contributor</td>
        <td></td>
    </tr>
    <tr>
        <td>Email</td>
        <td><?php echo $_GET["email"]; ?></td>
        <input type="hidden" name="email" value="<?php echo $_GET["email"]; ?>">
    </tr>
    <tr>
        <td>Family</td>
        <td></td>
    </tr>
    <tr>
        <td>Proposed Path Name</td>
        <td></td>
    </tr>
    <tr>
        <td>Description</td>
        <td></td>
    </tr>
    <tr>
        <td>Comment</td>
        <td></td>
    </tr>
    <tr>
        <td>Latitude</td>
        <td></td>
    </tr>
    <tr>
        <td>Longitude</td>
        <td></td>
    </tr>
    <tr>
        <td>Country</td>
        <td></td>
    </tr>
    <tr>
        <td>Ground Elevation</td>
        <td></td>
    </tr>
    <tr>
        <td>Elevation offset</td>
        <td></td>
    </tr>
    <tr>
        <td>True DB orientation</td>
        <td></td>
    </tr>
    <tr>
        <td>Corresponding Thumbnail</td>
        <td></td>
    </tr>
    <tr>
        <td>Corresponding AC3D File</td>
        <td></td>
    </tr>
    <tr>
        <td>Corresponding XML File</td>
        <td>
            <?php
            // Geshi stuff
            $source = file_get_contents('test.xml');
            $language = 'xml';
            $geshi = new GeSHi($source, $language);
            $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
            $geshi->set_line_style('background: #fcfcfc;');
            echo $geshi->parse_code();
            ?>
        </td>
    </tr>
    <tr>
        <td>Corresponding PNG Texture Files<br />(click on the pictures to get them bigger)</td>
        <td>
            <center>
            <a href="ContainerCrane.png" rel="lightbox[submission]" title="1st texture">#1<img src="ContainerCrane.png"></a>
            <a href="ATR42BR0.bmp" rel="lightbox[submission]" title="2nd texture">#2<img src="ATR42BR0.bmp"></a>
            <a href="ATR42BR2.bmp" rel="lightbox[submission]" title="3rd texture">#3<img src="ATR42BR2.bmp"></a>
            </center>
        </td>
    </tr>
    <tr>
        <td>Leave a comment to the submitter
        </td>
        <td><input type="text" name="maintainer_comment" size="100" value="Drop a comment to the submitter" /></td>
    </tr>
    <tr>
        <td>Action</td>
        <td><center>
        <input type="submit" name="action" value="Submit model" />
        <input type="submit" name="action" value="Reject model" />
        </center></td>
    </tr>
</form>
</table>
</p>
<?php
include '../../inc/footer.php';
}
?>
