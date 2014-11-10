<?php
require_once 'autoload.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();

$page_title = "TelaScience / OSGeo / FlightGear Landcover Database Mapserver";
$body_onload = "init()";
require 'inc/header.php';
?>
<br />
<center>
<object data="http://mapserver.flightgear.org/" type="text/html" width="800" height="1500"></object>
</center>
<?php
require 'inc/footer.php';
?>