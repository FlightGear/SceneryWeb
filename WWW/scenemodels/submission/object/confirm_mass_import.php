<?php
require_once "../../classes/DAOFactory.php";
require_once '../../classes/ObjectFactory.php';
require_once '../../classes/RequestMassiveObjectsAdd.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();
$requestDaoRW = DAOFactory::getInstance()->getRequestDaoRW();

// Inserting libs
require_once '../../inc/functions.inc.php';
require_once '../../inc/form_checks.php';
require_once '../../classes/EmailContentFactory.php';

$step = $_POST['step'];

if ($step == 1) {
    // Captcha stuff
    require_once '../../inc/captcha/recaptchalib.php';

    // Private key is needed for the server-to-Google auth.
    $privatekey = "6Len6skSAAAAACnlhKXCda8vzn01y6P9VbpA5iqi";
    $resp = recaptcha_check_answer ($privatekey,
                                    $_SERVER["REMOTE_ADDR"],
                                    $_POST["recaptcha_challenge_field"],
                                    $_POST["recaptcha_response_field"]);

    // What happens when the CAPTCHA was entered incorrectly
    if (!$resp->is_valid) {
        $page_title = "Automated Objects Massive Import Submission Form";
        $error_text = "<br />Sorry but the reCAPTCHA wasn't entered correctly. <a href='javascript:history.go(-1)'>Go back and try it again</a>" .
             "<br />(reCAPTCHA complained: " . $resp->error . ")<br />" .
             "Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
        include '../../inc/error_page.php';
        exit;
    }
}

$page_title = "Automated Objects Massive Import Submission Form";
require '../../inc/header.php';
?>
<script type ="text/javascript">
function update_countries(code,n) {
    for(var i = 1; i < n; i++) {
        if (document.getElementById("ob_country_"+i).value === "zz") {
            document.getElementById("ob_country_"+i).value=code;
        }
    }
}
</script>
<br />
<?php
global $error;
$error = false;

// Checking that email is valid (if it exists).
$failed_mail = false;
if (is_email($_POST['email'])) {
    $safe_email = htmlentities(stripslashes($_POST['email']));
} else {
    $failed_mail = true;
}

// Checking that comment exists. Just a small verification as it's not going into DB.
if (is_comment($_POST['comment'])) {
    $sent_comment = htmlentities(stripslashes($_POST['comment']));
}
else {
    echo "<p class=\"center warning\">Comment mismatch!</p>";
    $error = true;
    include '../../inc/footer.php';
    exit;
}

if ($step == 1) {
    if (!$failed_mail) {
        echo "<p class=\"center ok\">Email: ".$safe_email."</p>";
    } else {
        echo "<p class=\"center warning\">No email was given (not mandatory) or email mismatch!</p>";
    }
    
    // Checking that stg exists and is containing only letters or figures.
    if (isset($_POST['stg']) && preg_match($regex['stg'], $_POST['stg'])) {
        echo "<p class=\"center warning\">I'm sorry, but it seems that the content of your STG file is not correct (bad characters?). Please check again.</p>";
        $error = true;
        include '../../inc/footer.php';
        exit;
    }

    echo "<p class=\"center ok\">The content of the STG file seems correct, now proceeding with in-depth checks...</p>";
}

// If there is no false, generating SQL to be inserted into the database pending requests table.
if (!$error) {
    $tab_lines = explode("\n", $_POST['stg']);          // Exploding lines by carriage return (\n) in submission input.
    $tab_lines = array_map('trim', $tab_lines);         // Removing blank lines.
    $tab_lines = array_filter($tab_lines);              // Removing blank lines.
    $tab_lines = array_slice($tab_lines, 0, 100);       // Selects the 100 first elements of the tab (the 100 first lines not blank)

    $nb_lines = count($tab_lines);
    $global_ko = false;                                     // Validates - or no - the right to go further.
    $cpt_err = 0;                                       // Counts the number of errors.

    if ($step == 1) {
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

        echo "Please check the table below carefully, and make sure that your submission was read correctly. We have proposed a country for each object, but this may be inccorect. You can only change the countries on this page. Please <a href='javascript:history.go(-1)'>go back and edit your lines</a> if you would like to edit other things.";
    }

    $i = 1;
    $unknown_country = false;
    ?>
    <form id="positions" method="post" action="confirm_mass_import.php" onsubmit="return validateForm();">
    <?php
    echo "<table>\n";
    echo "<tr>\n<th>Line</th>\n<th>Type</th>\n<th>Model</th>\n<th>Longitude</th>\n<th>Latitude</th>\n<th>Elevation</th>\n<th>Orientation</th>\n<th>Elev. offset</th><th>Country</th>\n\n<th>Result</th>\n</tr>\n";

    $countries = $objectDaoRO->getCountries();
    $objectFactory = new ObjectFactory($objectDaoRO);
    $newObjects = array();
    
    foreach ($tab_lines as $value) { // Now printing the lines...
        $ko = false;
        
        $elevoffset = 0;
        echo "<tr>";
        echo "<td><center>".$i."</center></td>";
        $tab_tags = explode(" ",$value);
        $j = 1;
        
        // TODO : Have also to check the number of tab_tags returned!
        foreach ($tab_tags as $value_tag) { 
            switch($j) {
            case 1:  // Checking Label (must contain only letters and be strictly labelled OBJECT_SHARED for now)
                if (!strcmp($value_tag, "OBJECT_SHARED")) {
                    echo "<td><center>".$value_tag."</center></td> ";
                }
                else {
                    echo "<td><p class=\"center warning\">Object type Error!</p></td>";
                    $ko = true;
                    $cpt_err++;
                }
                break;
            case 2:  // Checking Shared model (Contains only figures, letters, _/. and must exist in DB)
                if (!preg_match($regex['model_filepath'], $value_tag)) {
                    $return_value = model_exists($value_tag);
                    if ($return_value == 0) {
                        echo "<td><center>".$value_tag."</center></td>";
                        $modelMD = $modelDaoRO->getModelMetadataFromName($value_tag);
                        $model_id = $modelMD->getId();
                    }
                    else if ($return_value == 1) {
                        echo "<td><p class=\"center warning\">Bad model label!</p></td>";
                        $ko = true;
                        $cpt_err++;
                    }
                    else if ($return_value == 2) {
                        echo "<td><p class=\"center warning\">Model unknown!</p></td>";
                        $ko = true;
                        $cpt_err++;
                    }
                    else if ($return_value == 3) {
                        echo "<td><p class=\"center warning\">Family unknown!</p></td>";
                        $ko = true;
                        $cpt_err++;
                    }
                }
                else {
                    echo "<td><p class=\"center warning\">Object Error!</p></td>";
                    $ko = true;
                    $cpt_err++;
                }

                break;
            case 3:  // Checking Longitude, must contain only figures and ., be >-180 and <180, be 20 characters max.
                if (is_longitude($value_tag)) {
                    echo "<td><center>".$value_tag."</center></td>";
                    $long = $value_tag;
                }
                else {
                    echo "<td><p class=\"center warning\">Longitude Error!</p></td>";
                    $ko = true;
                    $cpt_err++;
                }
                break;
            case 4:  // Checking Latitude, must contain only figures, - and ., be >-90 and <90, be 20 characters max.
                if (is_latitude($value_tag)) {
                    echo "<td><center>".$value_tag."</center></td>";
                    $lat = $value_tag;
                }
                else {
                    echo "<td><p class=\"center warning\">Latitude Error!</p></td>";
                    $ko = true;
                    $cpt_err++;
                }
                break;
            // Should we check that there is no other object declared at this position ? - we don't do it for unitary adding.
            case 5:  // Checking Elevation, must contain only figures and, be max 20 characters (TODO: can be used to automatically compute offset!!)
                if (is_gndelevation($value_tag)) {
                    echo "<td><center>".$value_tag."</center></td>";
                    $gndelev = $value_tag;
                }
                else {
                    echo "<td><p class=\"center warning\">Elevation Error!</p></td>";
                    $ko = true;
                    $cpt_err++;
                }
                break;
            case 6:  // Checking Orientation, must contain only figures, be >0, be 20 characters max.
                if (is_heading($value_tag)) {
                    echo "<td><center>".$value_tag."</center></td> ";
                    $orientation = $value_tag;
                }
                else {
                    echo "<td><p class=\"center warning\">Orientation Error!</p></td>";
                    $ko = true;
                    $cpt_err++;
                }
                break;

            case 7:  //If 7 columns, it's the offset. if 8 columns, it's pitch
                if (count($tab_tags)==7) {
                    if (is_offset($value_tag)) {
                        //echo "<td><center>".$value_tag."</center></td>";
                        $elevoffset = $value_tag;
                    }
                    else {
                        //echo "<td><p class=\"center warning\">Offset Error!</p></td>";
                        $ko = true;
                        $cpt_err++;
                    }
                }

                break;
            }
            $j++;
        }
        
        while ($j < 7) {
            echo "<td></td>";
            $j++;
        }

        echo "<td><center>".$elevoffset."</center></td> ";

        // Country
        if ($step == 1) {
            $ob_country = $objectDaoRO->getCountryAt($long, $lat)->getCode();
            if ($ob_country == "zz") {
                $unknown_country = true;
            }
            echo "<td><select name='ob_country_".$i."' id='ob_country_".$i."' style='width: 100%;'>";
            
            foreach($countries as $country) {
                echo "<option value=\"".$country->getCode()."\"";
                if ($country->getCode() == $ob_country) {
                    echo " selected";
                }
                echo ">".$country->getName()."</option>\n";
            }

            echo "</select></td>";
        } else {
            $ob_country = $_POST['ob_country_'.$i];
            echo "<td>".$countries[$ob_country]->getName()."</td>";
        }

        if (!$ko) {
            $newObject = $objectFactory->createObject(-1, $model_id, $long, $lat, $ob_country, 
                        $elevoffset, heading_stg_to_true($orientation), 1, $modelMD->getName());
            
            if ($objectDaoRO->checkObjectAlreadyExists($newObject)) {
                $ko = true;
                $global_ko = true;
                $cpt_err++;
                echo "<td style='background-color: rgb(200, 0, 0);'>Exists already</td>"; // Fatal error
            // this used to break the backend, testing if it still does
            } else {
                if ($objectDaoRO->detectNearbyObjects($lat, $long, $model_id)) {
                    echo "<td style='background-color: rgb(255, 200, 0);'>Nearby object</td>"; // Just a warning, not fatal
                } else {
                    echo "<td style='background-color: rgb(0, 200, 0); text-align: center;'>OK</td>";
                }
                
                $newObjects[] = $newObject;
            }
        }
        else {
            $global_ko = true;
            echo "<td style='background-color: rgb(200, 0, 0); text-align: center;'>KO</td>"; // Good or not ?
        }
        echo "</tr>\n";      // Finishes the line.
        $i++;                // Increments the line number.
    }
    if ($unknown_country) {
        echo "<tr><td colspan=\"8\" align=\"right\">Set all 'unknown' countries to:</td><td>" .
             "<select name='global_country' id='global_country' style='width: 100%;' onchange='update_countries(this.value,".$i.")'>" .
             "<option value=\"\">----</option>";

        foreach($countries as $country) {
            echo "<option value=\"".$country->getCode()."\">".$country->getName()."</option>\n";
        }
        echo "</select></td><td></td></tr>";
    }
    echo "</table>\n";

    echo "<b>Your comment:</b> ".$sent_comment."<br/>" .
         "<b>Your email:</b> ".$safe_email."<br/>" .
         "<input type='hidden' name='email' id='email' value='".$safe_email."'/>" .
         "<input type='hidden' name='comment' id='comment' value='".$sent_comment."'/>" .
         "<input name='stg' type='hidden' value='".$_POST['stg']."'/>";

    if ($global_ko) { // If errors have been found...
        if ($cpt_err == 1) {
            echo "<p class=\"center warning\">".$cpt_err." error has been found in your submission. Please <a href='javascript:history.go(-1)'>go back</a> and correct or delete the corresponding line from your submission before submitting again.</p>";
        } else {
            echo "<p class=\"center warning\">".$cpt_err." errors have been found in your submission. Please <a href='javascript:history.go(-1)'>go back</a> and correct or delete the corresponding lines from your submission before submitting again.</p>";
        }
        
        include '../../inc/footer.php';
        exit;
    }
}
if ($step == 1) {
    // Else, allow submitter to proceed
    echo "<p class=\"center ok\">No errors have been found in your submission, all fields have been checked and seem to be OK to be proceeded.<br/>".
         "Press to button below to finish your submission.<br/><br/>".
         "<input type='hidden' name='step' value='2'/><input name='submit' type='submit' value='Submit objects' /></p></form>";
} else {
    // Proceed on with the request generation
    $request = new RequestMassiveObjectsAdd();
    $request->setNewObjects($newObjects);
    $request->setContributorEmail($safe_email);
    $request->setComment($sent_comment);
    
    try {
        $updatedRequest = $requestDaoRW->saveRequest($request);
    } catch (Exception $ex) {
        // Talking back to submitter.
        echo "<p>Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.</p>";
        include '../../inc/footer.php';
        exit;
    }

    echo "<p class=\"center\">Your submission has been successfully queued into the FlightGear scenery database update requests!<br />";
    echo "Unless it's rejected, it should appear in Terrasync within a few days.<br />";
    echo "The FG community would like to thank you for your contribution!<br />";
    echo "Want to submit another position ?<br /> <a href=\"http://".$_SERVER['SERVER_NAME']."/submission/\">Click here to go back to the submission page.</a></p>";

    // Sending mail if there is no false and SQL was correctly inserted.
    // Sets the time to UTC.
    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');

    // Retrieving the IP address of the submitter (takes some time to resolve the IP address though).
    $ipaddr = htmlentities(stripslashes($_SERVER["REMOTE_ADDR"]));
    $host = gethostbyaddr($ipaddr);

    $emailSubmit = EmailContentFactory::getMassImportRequestPendingEmailContent($dtg, $ipaddr, $host, $updatedRequest);
    $emailSubmit->sendEmail("", true);
    
    // Mailing the submitter to tell that his submission has been sent for validation.
    if (!$failed_mail) {
        $emailSubmit = EmailContentFactory::getMassImportSentForValidationEmailContent($ipaddr, $host, $dtg, $updatedRequest);
        $emailSubmit->sendEmail($safe_email, false);
    }
}
require '../../inc/footer.php';
?>