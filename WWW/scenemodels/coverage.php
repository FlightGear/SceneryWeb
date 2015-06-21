<?php
require_once 'autoload.php';
$modelDaoRO = \dao\DAOFactory::getInstance()->getModelDaoRO();

require 'view/header.php';
?>
<h1>FlightGear Scenery Object Global Coverage</h1>
<div class="center">
    <object data="http://mapserver.flightgear.org/popmap?lat=10&amp;lon=0&amp;zoom=2"
        type="text/html" height="450" width="100%">
    </object>
</div>
<?php require 'view/footer.php'; ?>
