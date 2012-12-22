<?php

// Inserting libs
require_once('../../inc/functions.inc.php');

    // Checking DB availability before all
    $ok = check_availability();

    if (!$ok) {
        $page_title = "Automated Objects Massive Import Submission Form";
        $error_text = "Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.";
        include '../../inc/error_page.php';
        exit;
    }


    // Captcha stuff
    require_once('../../inc/captcha/recaptchalib.php');

    // Private key is needed for the server-to-Google auth.
    $privatekey = "6Len6skSAAAAACnlhKXCda8vzn01y6P9VbpA5iqi";
    $resp = recaptcha_check_answer ($privatekey,
                                    $_SERVER["REMOTE_ADDR"],
                                    $_POST["recaptcha_challenge_field"],
                                    $_POST["recaptcha_response_field"]);

    // What happens when the CAPTCHA was entered incorrectly
    if (!$resp->is_valid) {
        $page_title = "Automated Objects Massive Import Submission Form";
        $error_text = "<br />Sorry but the reCAPTCHA wasn't entered correctly. <a href='index_mass_import.php'>Go back and try it again</a>" .
             "<br />(reCAPTCHA complained: " . $resp->error . ")<br />" .
             "Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
        include '../../inc/error_page.php';
        exit;
    }

    $page_title = "Automated Objects Massive Import Submission Form";
    include '../../inc/header.php';
?>
<br />
<?php
    global $error;
    $error = false;

    // Checking that email is valid (if it exists).
    //(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
    $failed_mail = false;
    if (isset($_POST['email'])
        && (strlen($_POST['email']) > 0)
        && (strlen($_POST['email']) <= 50)) {
        $safe_email = pg_escape_string(stripslashes($_POST['email']));
        echo "<p class=\"center ok\">Email: ".$safe_email."</p>";
    }
    else {
        echo "<p class=\"center warning\">No email was given (not mandatory) or email mismatch!</p>";
        $failed_mail = true;
    }

    // Checking that comment exists. Just a small verification as it's not going into DB.
    if (isset($_POST['comment'])
        && (strlen($_POST['comment']) > 0)
        && (preg_match('/^[A-Za-z0-9 \-\.\,]+$/u', $_POST['comment']))
        && (strlen($_POST['comment'] <= 100))) {
        $sent_comment = pg_escape_string(stripslashes($_POST['comment']));
    }
    else {
        echo "<p class=\"center warning\">Comment mismatch!</p>";
        $error = true;
        include '../../inc/footer.php';
        exit;
    }

    // Checking that stg exists and is containing only letters or figures.
    if (isset($_POST['stg']) && preg_match('/^[a-zA-Z0-9\_\.\-\,\/]+$/u', $_POST['stg'])) {
        echo "<p class=\"center warning\">I'm sorry, but it seems that the content of your STG file is not correct (bad characters?). Please check again.</p>";
        $error = true;
        include '../../inc/footer.php';
        exit;
    }

    echo "<p class=\"center ok\">The content of the STG file seems correct, now proceeding with in-depth checks...</p>";


// If there is no false, generating SQL to be inserted into the database pending requests table.
if (!$error) {
    $tab_lines = explode("\n", $_POST['stg']);          // Exploding lines by carriage return (\n) in submission input.
    $tab_lines = array_map('trim', $tab_lines);         // Removing blank lines.
    $tab_lines = array_filter($tab_lines);              // Removing blank lines.
    $tab_lines = array_slice($tab_lines, 0, 100);       // Selects the 100th first elements of the tab (the 100th first lines not blank)

    $nb_lines = count($tab_lines);
    $global_ko = 0;                                     // Validates - or no - the right to go further.
    $cpt_err = 0;                                       // Counts the number of errors.

    echo '<p class=\"center\">Counted a number of '.$nb_lines.' lines submitted.</p>';

    // Limit the line numbers to
    if ($nb_lines > 100) {
        echo "<p class=\"center warning\">Too many lines submitted: 100 lines maximum per submission!</p>";
        include '../../inc/footer.php';
        exit;
    }

    if ($nb_lines < 1) {
        echo "<p class=\"center warning\">Not enough lines were submitted: 1 line minimum per submission!</p>";
        include '../../inc/footer.php';
        exit;
    }

    $i = 1;
    $ko = 0;
    ?>
    <form id="positions" method="post" action="check_mass_import2.php" onsubmit="return validateForm();">
    <?php
    echo "<table>\n";
    echo "<tr>\n<th>Line #</th>\n<th>Type</th>\n<th>Model</th>\n<th>Longitude</th>\n<th>Latitude</th>\n<th>Country</th>\n<th>Elevation</th>\n<th>Orientation</th>\n<th>Elev. offset</th>\n<th>Result</th>\n</tr>\n";

    foreach ($tab_lines as $value) { // Now printing the lines...
        $elevoffset = 0;
        echo "<tr>";
        echo "<td><center>".($i)."</center></td>";
        $tab_tags = explode(" ",$value);
        $j = 1;

        foreach ($tab_tags as $value_tag) { // !=> Have also to check the number of tab_tags returned!
            switch($j) {
            case 1:  // Checking Label (must contain only letters and be strictly labelled OBJECT_SHARED for now)
                if (!strcmp($value_tag, "OBJECT_SHARED")) {
                    echo "<td><center>".$value_tag."</center></td> ";
                }
                else {
                    echo "<td><p class=\"center warning\">Object type Error!</p></td>";
                    $ko = 1;
                    $global_ko = 1;
                    $cpt_err++;
                }
                break;
            case 2:  // Checking Shared model (Contains only figures, letters, _/. and must exist in DB)
                if (!preg_match("/^[a-z0-9_\/.-]$/i",$value_tag)) {
                    $return_value = model_exists($value_tag);
                    if ($return_value == 0) {
                        echo "<td><center>".$value_tag."</center></td>";
                        $model_id = ob_model_from_name($value_tag);
                    }
                    else if ($return_value == 1) {
                        echo "<td><p class=\"center warning\">Bad model label!</p></td>";
                        $ko = 1;
                        $global_ko = 1;
                        $cpt_err++;
                    }
                    else if ($return_value == 2) {
                        echo "<td><p class=\"center warning\">Object unknown!</p></td>";
                        $ko = 1;
                        $global_ko = 1;
                        $cpt_err++;
                    }
                    else if ($return_value == 3) {
                        echo "<td><p class=\"center warning\">Family unknown!</p></td>";
                        $ko = 1;
                        $global_ko = 1;
                        $cpt_err++;
                    }
                }
                else {
                    echo "<td><p class=\"center warning\">Object Error!</p></td>";
                    $ko = 1;
                    $global_ko = 1;
                    $cpt_err++;
                }

                break;
            case 3:  // Checking Longitude, must contain only figures and ., be >-180 and <180, be 20 characters max.
                if ((strlen($value_tag) <= 20)
                    && ($value_tag <= 180)
                    && ($value_tag >= -180)
                    && preg_match('/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u', $value_tag)) {
                    echo "<td><center>".$value_tag."</center></td>";
                    $long = $value_tag;
                }
                else {
                    echo "<td><p class=\"center warning\">Longitude Error!</p></td>";
                    $ko = 1;
                    $global_ko = 1;
                    $cpt_err++;
                }
                break;
            case 4:  // Checking Latitude, must contain only figures, - and ., be >-90 and <90, be 20 characters max.
                if ((strlen($value_tag) <= 20)
                    && ($value_tag <= 90)
                    && ($value_tag >= -90)
                    && preg_match('/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u', $value_tag)) {
                    echo "<td><center>".$value_tag."</center></td>";
                    $lat = $value_tag;
                }
                else {
                    echo "<td><p class=\"center warning\">Latitude Error!</p></td>";
                    $ko = 1;
                    $global_ko = 1;
                    $cpt_err++;
                }
                break;
            case 5:  // Country
                echo "<select name='ob_country_".$j."' id='ob_country_".$j."'>";
                     list_countries_select(compute_country_code_from_position($long, $lat));
                echo "</select>";
                break;
            // Should we check that there is no other object declared at this position ? - we don't do it for unitary adding.
            case 6:  // Checking Elevation, must contain only figures and, be max 20 characters
                if ((strlen($value_tag) <= 20)
                    && preg_match('/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u', $value_tag)) {
                    echo "<td><center>".$value_tag."</center></td>";
                    $gndelev = $value_tag;
                }
                else {
                    echo "<td><p class=\"center warning\">Elevation Error!</p></td>";
                    $ko = 1;
                    $global_ko = 1;
                    $cpt_err++;
                }
                break;
            case 7:  // Checking Orientation, must contain only figures, be >0, be 20 characters max.
                if ((strlen($value_tag) <= 20)
                    && ($value_tag >= 0)
                    && preg_match('/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u', $value_tag)) {
                    echo "<td><center>".$value_tag."</center></td> ";
                    $orientation = $value_tag;
                }
                else {
                    echo "<td><p class=\"center warning\">Orientation Error!</p></td>";
                    $ko = 1;
                    $global_ko = 1;
                    $cpt_err++;
                }
                break;

            case 8:  //If 8 columns, it's the offset. if 9 columns, it's pitch
                if (count($tab_tags)==8) {
                    if ((strlen($value_tag) <= 20)
                        && preg_match('/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u', $value_tag)) {
                        //echo "<td><center>".$value_tag."</center></td>";
                        $elevoffset = $value_tag;
                    }
                    else {
                        //echo "<td><p class=\"center warning\">Offset Error!</p></td>";
                        $ko = 1;
                        $global_ko = 1;
                        $cpt_err++;
                    }
                }

                break;
            }
            $j++;
        }

        echo "<td><center>".$elevoffset."</center></td> ";

        if ($ko == 0) {
            if (detect_already_existing_object($lat, $long, $gndelev, $elevoffset, $orientation, $model_id)) {
                $ko = 1;
                $global_ko = 1;
                $cpt_err++;

                echo "<td><p class=\"center warning\">Already exists!</p></td>";
            } else {
                echo "<td><p class=\"center ok\">OK</p></td>";
                $data_rw[$i]="('', ST_PointFromText('POINT(".$long." ".$lat.")', 4326), ".$gndelev.", ".$elevoffset.", ".heading_stg_to_true($orientation).", ".$model_id.", 1)";
            }
        }
        else {
            echo "<td><p class=\"center warning\">KO</p></td>"; // Good or not ?
        }
        echo "</tr>\n";      // Finishes the line.
        $i++;                // Increments the line number.
        $ko = 0;             // Resets the local KO to "0".
    }
    echo "</table>\n";
    echo "<br />";

    if ($global_ko == 1) { // If errors have been found...
        if ($cpt_err == 1) {
            echo "<p class=\"center warning\">".$cpt_err." error has been found in your submission. Please correct or delete the corresponding line from your submission before submitting again.</p>";
            include '../../inc/footer.php';
            exit;
        }
        else {
            echo "<p class=\"center warning\">".$cpt_err." errors have been found in your submission. Please correct or delete the corresponding line from your submission before submitting again.</p>";
            include '../../inc/footer.php';
            exit;
        }
    }

    // Else, proceed on with the request generation
    echo "<p class=\"center ok\">No error has been found in your submission, all fields have been checked and seem to be OK to be proceeded.</p><br /></form>";
}
include '../../inc/footer.php';
?>
