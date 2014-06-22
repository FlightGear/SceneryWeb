<?php
/**
 * This script creates an xml file containing the country code according
 *
 */
require_once "../../classes/DAOFactory.php";
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();

// Inserting libs
require_once 'functions.inc.php';

$long=pg_escape_string($_GET['lg']);
$lat=pg_escape_string($_GET['lt']);

header('Content-Type: text/xml');
echo "<?xml version=\"1.0\" standalone=\"yes\" ?>\n".
     "<country>".
     $objectDaoRO->getCountryAt($long, $lat)->getCode().
     "</country>";

?>
