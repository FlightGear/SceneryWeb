<?php
require_once "classes/DAOFactory.php";
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();

require 'inc/header.php';
?>
<h1>FlightGear Scenery Object Global Coverage</h1>
<div class="center">
    <object data="http://mapserver.flightgear.org/popmap?lat=10&amp;lon=0&amp;zoom=2"
        type="text/html" height="450" width="100%">
    </object>
</div>
<?php require 'inc/footer.php'; ?>
