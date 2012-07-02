<?php

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
// Geshi stuff
    $source = file_get_contents('test.xml');
    $language = 'xml';
    $geshi = new GeSHi($source, $language);
    $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
    $geshi->set_line_style('background: #fcfcfc;');
    echo $geshi->parse_code();

?>
</p>
<a href="ContainerCrane.png" rel="lightbox[submission]" title="1st texture">image #1</a>
<a href="ATR42BR0.bmp" rel="lightbox[submission]" title="2nd texture">image #2</a>
<a href="ATR42BR2.bmp" rel="lightbox[submission]" title="3rd texture">image #3</a>
<p class="center">
<input type="text" name="maintainer_comment" value="Drop a comment to user" />
<input type="submit" name="submit" value="Submit model" />
<input type="submit" name="reject" value="Reject model" />
</p>
<?php
include '../../inc/footer.php';
?>
