<?php
require_once 'classes/DAOFactory.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();

$page_title = "FlightGear World Scenery v2.10.0";
require 'inc/header.php';
?>
<br />
<center>
<object data="http://www.flightgear.org/legacy-Downloads/scenery-v2.10.html" type="text/html" width="800" height="600"></object>
</center>
<?php
require 'inc/footer.php';
?>
