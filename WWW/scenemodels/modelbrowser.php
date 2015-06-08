<?php
require_once 'autoload.php';
$modelDaoRO = \dao\DAOFactory::getInstance()->getModelDaoRO();

require 'inc/header.php';

if (isset($_REQUEST['offset']) && preg_match(FormChecker::$regex['pageoffset'],$_REQUEST['offset'])) {
    $offset = $_REQUEST['offset'];
} else {
    $offset = 0;
}

$pagesize = 99;


if (isset($_REQUEST['shared']) && FormChecker::isModelGroupId($_REQUEST['shared'])) {
    $modelGroupId = $_REQUEST['shared'];
}

if (isset($modelGroupId)) {
    if ($modelGroupId >= 0) {
        $group = $modelDaoRO->getModelsGroup($modelGroupId);
        $title = "Model Browser: ".$group->getName();
        $modelMetadatas = $modelDaoRO->getModelMetadatasByGroup($modelGroupId, $offset, $pagesize);
    }
    else {
        $title = "FlightGear Scenery Static Model Browser";
        $modelMetadatas = $modelDaoRO->getModelMetadatasByGroup(0, $offset, $pagesize);
    }
}
else {
    $modelMetadatas = $modelDaoRO->getModelMetadatas($offset, $pagesize);
    $title = "FlightGear Scenery Model Browser";
}
?>

<h1><?php echo $title;?></h1>
<table>
    <tr class="bottom">
        <td align="center">
        <a href="modelbrowser.php?offset=<?php echo $offset-$pagesize;if (isset($modelGroupId)) {echo "&amp;shared=".$modelGroupId;}?>">Prev</a>
        <a href="modelbrowser.php?offset=<?php echo $offset+$pagesize;if (isset($modelGroupId)) {echo "&amp;shared=".$modelGroupId;}?>">Next</a>
        </td>
    </tr>
    <tr>
        <td>
        <script type="text/javascript">var noPicture = false</script>
        <script src="inc/js/image_trail.js" type="text/javascript"></script>
        <div id="trailimageid" style="position:absolute;z-index:10000;overflow:visible"></div>
<?php
        foreach ($modelMetadatas as $modelMetadata) {
?>
            <a href="/modelview.php?id=<?php echo $modelMetadata->getId();?>">
            <img title="<?php echo $modelMetadata->getName().' ['.$modelMetadata->getFilename().']';?>"
                src="modelthumb.php?id=<?php echo $modelMetadata->getId();?>" width="100" height="75"
                onmouseover="showtrail('modelthumb.php?id=<?php echo $modelMetadata->getId();?>','','','1',5,322);"
                onmouseout="hidetrail();"
                alt="" />
        </a>
<?php
        }
?>
        </td>
    </tr>
    <tr class="bottom">
        <td align="center">
        <a href="modelbrowser.php?offset=<?php echo $offset-$pagesize;if (isset($modelGroupId)) {echo "&amp;shared=".$modelGroupId;}?>">Prev</a>
        <a href="modelbrowser.php?offset=<?php echo $offset+$pagesize;if (isset($modelGroupId)) {echo "&amp;shared=".$modelGroupId;}?>">Next</a>
        </td>
    </tr>
</table>
<?php require 'inc/footer.php';?>