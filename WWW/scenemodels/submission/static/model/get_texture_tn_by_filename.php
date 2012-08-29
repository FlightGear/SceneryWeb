<?php
    $mo_sig = $_GET["mo_sig"];
    $dir_array = preg_split("/\//", $_GET['name']);
    $filename = $dir_array[count($dir_array)-1];
    //$texture_file = "http://".$_SERVER['SERVER_NAME'] ."/submission/static/model/get_texture_by_filename.php?mo_sig=".$mo_sig."&name=".$name;
    //$texture_file = "http://scenemodels.flightgear.org/submission/static/model/get_texture_by_filename.php?mo_sig=".$mo_sig."&name=".$name;



    // Inserting libs
    require_once ('../../../inc/functions.inc.php');

    if (!isset($filename) || !preg_match("/[0-9a-zA-Z_.-]/", $filename))
        exit;

    if (!isset($mo_sig) || (strlen($mo_sig) != 64) || !preg_match("/[0-9a-z]/", $mo_sig))
        exit;

    $resource_rw = connect_sphere_rw();

    // If connection is not OK
    if ($resource_rw == '0')
        exit;

    // Checking the presence of sig into the database
    $result = @pg_query($resource_rw, "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $mo_sig ."';");
    if (pg_num_rows($result) != 1)
        exit;

    // Now we are sure there is only 1 row
    $row = pg_fetch_row($result);
    $sqlzbase64 = $row[1];

    // Base64 decode the query
    $sqlz = base64_decode($sqlzbase64);

    // Gzuncompress the query
    $query_rw = gzuncompress($sqlz);

    $pattern = "/INSERT INTO fgs_models \(mo_id, mo_path, mo_author, mo_name, mo_notes, mo_thumbfile, mo_modelfile, mo_shared\) VALUES \(DEFAULT, '(?P<path>[a-zA-Z0-9_.-]+)', (?P<author>[0-9]+), '(?P<name>[a-zA-Z0-9,;:?@ !_.-]+)', '(?P<notes>[a-zA-Z0-9 ,!_.-]*)', '(?P<thumbfile>[a-zA-Z0-9=+\/]+)', '(?P<modelfile>[a-zA-Z0-9=+\/]+)', (?P<shared>[0-9]+)\) RETURNING mo_id/";
    preg_match($pattern, $query_rw, $matches);

    $mo_modelfile = $matches['modelfile'];

    // Prepare the tmp directory

    // Managing possible concurrent accesses on the maintainer side.
    $target_path = sys_get_temp_dir() .'/submission_'.random_suffix();

    while (file_exists($target_path)) {
        usleep(500);    // Makes concurrent access impossible: the script has to wait if this directory already exists.
    }

    if (!mkdir($target_path)) {
        echo "Impossible to create ".$target_path." directory!";
    }

    if (file_exists($target_path) && is_dir($target_path)) {
        $archive = base64_decode ($mo_modelfile);           // DeBase64 file
        $file = $target_path.'/submitted_files.tar.gz';     // Defines the destination file
        file_put_contents ($file, $archive);                // Writes the content of $mo_modelfile into submitted_files.tar.gz
    }

    $detar_command = 'tar xvzf '.$target_path.'/submitted_files.tar.gz -C '.$target_path. '> /dev/null';
    system($detar_command);


    // Looking for the file in the tmp directory
    $dir = opendir($target_path);

    while (false !== ($file = readdir($dir))) {
        // If we know the extension
        if (isset($extension) && show_file_extension($file) == "png") {
            $fichier = $target_path."/".$file;
            break;
        }
    }

    $img = imagecreatefrompng( $fichier );
    $width = imagesx( $img );echo $fichier."--".$width;
    $height = imagesy( $img );

    // calculate thumbnail size
    $new_width = 128;
    $new_height = floor( $height * ( $new_width / $width ) );

    // create a new temporary image
    $tmp_img = imagecreatetruecolor( $new_width, $new_height );

    // copy and resize old image into new image 
    imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
     
    header('Content-Type: image/png');
    imagepng($tmp_img);
    
    imagedestroy($tmp_img);
    
    // Ok, now we can delete the stuff we used - at least I think so ;-)
    // This should be done at the end of the script
    unlink($target_path.'/submitted_files.tar.gz');  // Deletes compressed file
    clear_dir($target_path);                          // Deletes temporary submission directory
?>
