<?php
/**
* To change the title, create a $pageTitle string variable before including this file
* To change body onload, create a $body_onload string variable before including this file
*/
?>

<!DOCTYPE html>

<html>
<head>
    <meta charset="utf-8"/>
    <link rel="stylesheet" href="/css/style.css" type="text/css"/>
    <link rel="stylesheet" href="/css/lightbox.css" type="text/css"/>
    <title><?php echo (isset($pageTitle))?$pageTitle:"FlightGear Scenery Website";?></title>

    <?php
    if(!isset($nojs_page)) {
    ?>
        <script type="text/javascript">
            /*<![CDATA[*/
            document.write("<!-- ");
            /*]]>*/
        </script>
        <meta id="refresh" http-equiv="Refresh" content="0;URL='/nojs.php'" />
        <script type="text/javascript">
            /*<![CDATA[*/
            document.write(' --><script type=\"text/javascript\">');
            /*]]>*/
        </script>
    <?php
    }
    ?>
    <script type="text/javascript" src="/inc/js/jquery.js"></script>
    <script type="text/javascript" src="/inc/js/lightbox/lightbox.js"></script>

    <script type="text/javascript">
    $('refresh').remove();
    </script>
</head>
<body<?php echo (isset($body_onload))?" onload='$body_onload'":"";?>>

    <div id="content">  
        <div class="titleback">
            <img src="/img/banner.jpg" alt="Flightgear logo"/>
        </div>

        <?php require 'menu.php';?>
        
        <div id="content2">
<!--        <h3 class="warning">Service degraded, starting 2015-04-04 late evening (UTC) for approx 24 hours due to database upgrade.</h3> -->
