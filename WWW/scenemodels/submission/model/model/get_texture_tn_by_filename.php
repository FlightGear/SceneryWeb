<?php
$mo_sig = $_GET["mo_sig"];
$dir_array = preg_split("/\//", $_GET['name']);
$filename = $dir_array[count($dir_array)-1];

// Inserting libs
require_once '../../../inc/functions.inc.php';
require_once '../../../inc/form_checks.php';

if (!isset($filename) || !preg_match($regex['filename'], $filename))
    exit;

if (!is_sig($mo_sig))
    exit;

$resource_rw = connect_sphere_rw();

// If connection is not OK
if ($resource_rw == '0')
    exit;

// Checking the presence of sig into the database
$result = @pg_query($resource_rw, "SELECT spr_base64_sqlz " .
                                  "FROM fgs_position_requests " .
                                  "WHERE spr_hash = '". $mo_sig ."';");
if (pg_num_rows($result) != 1)
    exit;

// Now we are sure there is only 1 row
$row = pg_fetch_row($result);
$sqlzbase64 = $row[0];

// Base64 decode the query
$sqlz = base64_decode($sqlzbase64);

// Gzuncompress the query
$query_rw = gzuncompress($sqlz);

$pattern = "/INSERT INTO fgs_models \(mo_id, mo_path, mo_author, mo_name, mo_notes, mo_thumbfile, mo_modelfile, mo_shared\) VALUES \(DEFAULT, '(?P<path>[a-zA-Z0-9_.-]+)', (?P<author>[0-9]+), '(?P<name>[a-zA-Z0-9,;:?@ !_.-]+)', '(?P<notes>[a-zA-Z0-9 ,!_.-]*)', '(?P<thumbfile>[a-zA-Z0-9=+\/]+)', '(?P<modelfile>[a-zA-Z0-9=+\/]+)', (?P<shared>[0-9]+)\) RETURNING mo_id/";
preg_match($pattern, $query_rw, $matches);

$mo_modelfile = base64_decode($matches['modelfile']);

// Prepare the tmp directory

// Managing possible concurrent accesses on the maintainer side.
$target_path = open_tgz($mo_modelfile);

// Looking for the file in the tmp directory
$dir = opendir($target_path);

while (false !== ($filename = readdir($dir))) {
    // If we know the extension
    if (show_file_extension($filename) == "png" && $filename == $filename) {
        $filepath = $target_path."/".$filename;
        break;
    }
}

$img = imagecreatefrompng( $filepath );
$width = imagesx( $img );
$height = imagesy( $img );

header('Content-Type: image/png');

if ($width>256) {
    // calculate thumbnail size
    $new_width = 256;
    $new_height = floor( $height * ( $new_width / $width ) );

    // create a new temporary image
    $tmp_img = imagecreatetruecolor( $new_width, $new_height );

    // copy and resize old image into new image 
    imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
     
    // Display the PNG directly to the browser
    imagepng($tmp_img);
    
    imagedestroy($tmp_img);
}
else {
    readfile($filepath);
}

// Ok, now we can delete the stuff we used - at least I think so ;-)
// This should be done at the end of the script
close_tgz($target_path);
?>
