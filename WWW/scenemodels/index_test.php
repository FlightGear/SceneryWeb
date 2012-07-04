<?php
    // Inserting libs
    require_once ('inc/functions.inc.php');
?>
<html>
<head>
    <link rel="stylesheet" href="/css/lightbox.css" type="text/css"/>
</head>
<body>
<script type="text/javascript" src="/inc/js/lightbox/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="/inc/js/lightbox/lightbox.js"></script>
<?php
srand((double)microtime()*1000000);
$affimage = rand(1, 2477);
// There is still the problem of the "no image". Should have to do a md5sum on the Base64 to check the returned image is not blank.
?>

<table style="border-style: solid; border-width: 1px;" cellpadding="1" cellspacing="1" rules="rows">
        <tr>
        <td>
            <iframe
                src="http://mapserver.flightgear.org/lightmap/?lon=-117.12099&amp;lat=32.73356&amp;zoom=12"
                width="480" height="360"
                scrolling="no"
                marginwidth="2" marginheight="2"
                frameborder="0">
            </iframe>
        </td>
        <td>
            <center>
            <?php
            for ($i=0; $i<2477; $i++) {
                    if ($i == $affimage) {
                        echo "<a href=\"http://scenemodels.flightgear.org/modeledit.php?id=".$i."\" rel=\"lightbox[submission]\" title=\"".$i."st texture\"><img src=\"modelthumb.php?id=".$affimage."\"></a>\n";
                    }
                    else
                        echo "<a href=\"http://scenemodels.flightgear.org/modeledit.php?id=".$i." rel=\"lightbox[submission]\" title=\"1st texture\"></a>\n";
            }
?>
            </center><br />
            <center>Discover this model and more <a href="http://scenemodels.flightgear.org/modeledit.php?id=<?php echo $affimage; ?>">here</a></center>
        </td>
        </tr>
    </table>
</center>
</body>
</html>
