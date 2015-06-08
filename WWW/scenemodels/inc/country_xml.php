<?php
/**
 * This script creates an xml file containing the country code according
 *
 */
require_once '../autoload.php';
$objectDaoRO = \dao\DAOFactory::getInstance()->getObjectDaoRO();

// Inserting libs
require_once 'functions.inc.php';

$long = $_GET['lg'];
$lat = $_GET['lt'];

header('Content-Type: text/xml');
echo "<?xml version=\"1.0\" standalone=\"yes\" ?>\n".
     "<country>".
     $objectDaoRO->getCountryAt($long, $lat)->getCode().
     "</country>";

?>
