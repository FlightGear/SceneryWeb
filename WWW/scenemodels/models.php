<?php
require_once 'classes/DAOFactory.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();

require 'inc/header.php';

if(isset($_REQUEST['offset']) && preg_match('/^[0-9]+$/u',$_REQUEST['offset'])){
    $offset = $_REQUEST['offset'];
} else {
    $offset=0;
}

$pagesize = 10;
    
?>

<script type="text/javascript">
  function popmap(lat,lon,zoom) {
    popup = window.open("http://mapserver.flightgear.org/popmap?zoom="+zoom+"&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
    popup.focus();
  }
</script>

<h1>FlightGear Scenery Model Directory</h1>


<?php require 'inc/footer.php';?>
