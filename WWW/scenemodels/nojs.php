<?php
$nojs_page = true;
require_once 'autoload.php';
require 'inc/header.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
?>
<h1>Your Javascript is disabled</h1>

<p>For better navigation, please activate Javascript!</p>

<?php require 'inc/footer.php';?>
