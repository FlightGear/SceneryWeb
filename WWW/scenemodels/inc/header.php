<?php
/**
 * To change the title, create a $page_title string variable before including this file
 * To change body onload, create a $body_onload string variable before including this file
 */
?>

<?php $link = pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable'); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="/css/style.css" type="text/css"/>
    <link rel="stylesheet" href="/css/lightbox.css" type="text/css"/>
    <title><?php echo (isset($page_title))?$page_title:"FlightGear Scenery Database";?></title>
<?php
    if(!isset($nojs_page)) {
?>
    <noscript>
        <meta http-equiv="refresh" content="0; URL='/inc/nojs.php'"/>
    </noscript>
<?php
    }
?>

  </head>
  <body <?php echo (isset($body_onload))?"onload='$body_onload'":"";?>>

  <table>
    <tr>
      <td class="titleback">
        <img src="/img/banner.jpg" alt="Flightgear logo"/>
      </td>
    </tr>
  </table>
<?php include 'menu.php';?>
