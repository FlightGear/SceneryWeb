<?php
require_once 'autoload.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();

require 'inc/header.php';

if(isset($_REQUEST['offset']) && preg_match(FormChecker::$regex['pageoffset'],$_REQUEST['offset'])){
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

  <table>
    <tr class="bottom">
        <td colspan="2" align="center">
<?php 
            if ($offset >= $pagesize) {
                echo "<a href=\"models.php?offset=".($offset-$pagesize)."\">Prev</a> | ";
            }
?>
            <a href="models.php?offset=<?php echo $offset+$pagesize;?>">Next</a>
        </td>
    </tr>
<?php
    $modelMetadatas = $modelDaoRO->getModelMetadatas($offset, $pagesize);
    
    foreach ($modelMetadatas as $modelMetadata) {
        echo "<tr>\n" .
             "<td style=\"width: 320px\">\n" .
             "<a href=\"modelview.php?id=".$modelMetadata->getId()."\"><img src=\"modelthumb.php?id=".$modelMetadata->getId()."\" alt=\"Model ".$modelMetadata->getId()."\"/></a>\n" .
             "</td>\n" .
             "<td>\n" .
             "<ul class=\"table\">" .
             "<li><b>Name:</b> ".$modelMetadata->getName()."</li>\n" .
             "<li><b>Path:</b> ".$modelMetadata->getFilename()."</li>\n";
        if ($modelMetadata->getDescription() !== NULL && strlen($modelMetadata->getDescription())>0) {
            echo "<li><b>Notes:</b> ".$modelMetadata->getDescription()."</li>\n";
        }
        echo "<li><b>Author: </b><a href=\"author.php?id=".$modelMetadata->getAuthor()->getId()."\">".$modelMetadata->getAuthor()->getName()."</a></li>\n" .
             "<li><b>Last Updated: </b>".$modelMetadata->getLastUpdated()->format("Y-m-d (H:i)")."</li>\n" .
             "<li><b>Type: </b><a href=\"modelbrowser.php?shared=".$modelMetadata->getModelsGroup()->getId()."\">".$modelMetadata->getModelsGroup()->getName()."</a></li>\n";

        if ($modelMetadata->getModelsGroup()->isStatic()) {
            $objects = $objectDaoRO->getObjectsByModel($modelMetadata->getId());

            foreach ($objects as $object) {
                $objPos = $object->getPosition();
                echo "<li>(<a href=\"download/".$object->getDir().".tgz\">".$object->getDir()."</a>) ";
                echo "<a href=\"javascript:popmap(".$objPos->getLatitude().",".$objPos->getLongitude().",13)\">Map</a></li>\n";
            }
        }

        echo "<li><a href=\"modelview.php?id=".$modelMetadata->getId()."\">View more about this model.</a></li>\n";
        echo "</ul>";
        echo "</td>\n";
        echo "</tr>\n";
    }
?>
    <tr class="bottom">
        <td colspan="2" align="center">
            <?php 
            if ($offset >= $pagesize) {
                echo "<a href=\"models.php?offset=".($offset-$pagesize)."\">Prev</a> | ";
            }
            ?>
            <a href="models.php?offset=<?php echo $offset+$pagesize;?>">Next</a>
        </td>
    </tr>
  </table>
<?php require 'inc/footer.php';?>
