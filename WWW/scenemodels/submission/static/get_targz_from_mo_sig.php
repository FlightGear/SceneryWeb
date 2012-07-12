<?php

// Inserting libs
require_once ('../../inc/functions.inc.php');

// The goal of this small file is to give the possibility to download a model insertion request
// as a tar.gz file, containing model, textures, XML file.
// There is no other (known ;-) possibility to include this in the rest of the static submission script.

header("Content-type: multipart/x-gzip");

if ((isset($_GET["mo_sig"])) && ((strlen($_GET["mo_sig"])) == 64) && preg_match("/[0-9a-z]/", $_GET["mo_sig"])) {
        $resource_rw = connect_sphere_rw();

         // If connection is OK
        if($resource_rw != '0') {

            // Checking the presence of sig into the database
            $result = @pg_query($resource_rw, "select spr_hash, spr_base64_sqlz from fgs_position_requests where spr_hash = '". $_GET["mo_sig"] ."';");
            if (pg_num_rows($result) != 1) {
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
                        $trigged_query_rw = str_replace("INSERT INTO fgsoj_models (mo_id, mo_path, mo_author, mo_name, mo_notes, mo_thumbfile, mo_modelfile, mo_shared) VALUES (DEFAULT, ","",$query_rw); // Removing the start of the query from the data;
                        $tab_tags = explode(", ", $trigged_query_rw); // Separating the data based on ', '
                        $j = 0;
                        foreach ($tab_tags as $value_tag) {
                            $j++;
                            if ($j == 6) {
                                $mo_modelfile = str_replace("'", "", $value_tag);
                                echo base64_decode($mo_modelfile);
                            }
                        }
                    }
            }
        }
}

?>
