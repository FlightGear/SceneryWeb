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
        echo "<center>Deleting corresponding pending query.</center>";
            if ((isset($_POST["sig"]))) {
                $resource_rw = connect_sphere_rw();

                // If connection is OK
                if ($resource_rw != '0') {

                // Checking the presence of sig into the database
                $result = @pg_query($resource_rw, "select spr_hash, spr_base64_sqlz from fgs_position_requests where spr_hash = '". $_POST["sig"] ."';");
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
                    // Delete the entry from the pending query table.
                    $delete_request = "delete from fgs_position_requests where spr_hash = '". $_POST["sig"] ."';";
                    $resultdel = @pg_query($resource_rw, $delete_request);

                    if (!resultdel) {
                        $page_title = "Automated Shared Models Positions Pending Requests Form";
                        include '../../inc/header.php';
                        echo "<center>\n";
                        echo "Signature found.<br /> Now deleting request with number ". $_POST[sig].".<br />";
                        echo "<font color=\"red\">Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</font><br />\n";
                        echo "</center>\n";

                        // Closing the rw connection.
                        include '../../inc/footer.php';
                        pg_close($resource_rw);
                        exit;
                    }
                    else {
                        $page_title = "Automated Shared Models Positions Pending Requests Form";
                        include '../../inc/header.php';
                        echo "<center>";
                        echo "Signature found.<br />Now deleting request with number ". $_POST[sig].".<br />";
                        echo "<font color=\"green\">Entry has correctly been deleted from the pending requests table.</font>";
                        echo "</center>";

                        // Closing the rw connection.
                        include '../../inc/footer.php';
                        pg_close($resource_rw);

                        // Sending mail if entry was correctly deleted.
                        // Sets the time to UTC.

                        date_default_timezone_set('UTC');
                        $dtg = date('l jS \of F Y h:i:s A');

                        // OK, let's start with the mail redaction.
                        // Who will receive it ?
                        $to = "\"Olivier JACQ\" <olivier.jacq@free.fr>, ";
                        if(isset($_POST['email'])) {
                            // $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                            $to .= $_POST["email"];
                        }
                        else {
                            // $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                        }

                        // What is the subject ?
                        $subject = "[FG Scenery Submission forms] Automatic 3D model insertion DB reject and deletion confirmation.";

                        // Generating the message and wrapping it to 77 signs per line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
                        $message0 = "Hi,"  . "\r\n" .
                                    "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                                    "http://scenemodels.flightgear.org/static/static_submission.php"  . "\r\n" .
                                    "I just wanted to let you know that the 3D model import named  Blah."."\r\n" .
                                    "has been rejected and successfully deleted from the pending requests table.";

                        $message = wordwrap($message0, 77, "\r\n");

                        // Preparing the headers.
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "From: \"FG Scenery Pending Requests forms\" <martin.spott@mgras.net>" . "\r\n";
                        $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

                        // Let's send it ! No management of mail() errors to avoid being too talkative...
                        @mail($to, $subject, $message, $headers);
                        exit;
                        }
                    }
                    echo "The user submission has been rejected with the following warning: ".$_POST["maintainer_comment"].". User has been informed by mail.";
    }}}

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
    if ((isset($_GET["sig"])) && ((strlen($_GET["sig"])) == 64) && preg_match("/[0-9a-z]/", $_GET["sig"])) {
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
        <input type="hidden" name="sig" value="<?php echo $_GET["sig"]; ?>" />
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
