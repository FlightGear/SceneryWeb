<?php
require 'view/header.php';
?>

<script type="text/javascript">
function popmap(lat,lon,zoom) {
    popup = window.open("http://mapserver.flightgear.org/popmap?zoom="+zoom+"&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
    popup.focus();
}
</script>

<?php
    
echo "<h1>".$modelMetadata->getName()."</h1>";
if (!empty($modelMetadata->getDescription())) {
    echo "<p>".$modelMetadata->getDescription()."</p>";
}
?>
<table>
    <tr>
        <td style="width: 320px" rowspan="7"><img src="app.php?c=Models&amp;a=thumbnail&amp;id=<?php print $modelMetadata->getId(); ?>" alt=""/></td>
        <td>File name</td>
        <td>
<?php
            print $modelMetadata->getFilename();
?>
        </td>
    </tr>
    <tr>
        <td>Type</td>
        <td>
            <a href="app.php?c=Models&amp;a=browse&amp;shared=<?php echo $modelMetadata->getModelsGroup()->getId()?>">
                <?php echo $modelMetadata->getModelsGroup()->getName()?>
            </a>
        </td>
    </tr>
    <tr>
        <td>Author</td>
        <td>
<?php
            print "<a href=\"author.php?id=".$modelMetadata->getAuthor()->getId()."\">".$modelMetadata->getAuthor()->getName()."</a>";
?>
        </td>
    </tr>
    <tr>
        <td>Last updated</td>
        <td><?php print $modelMetadata->getLastUpdated()->format("Y-m-d (H:i)"); ?></td>
    </tr>
    <tr>
        <td>Model ID</td>
        <td><?php print $id; ?></td>
    </tr>
    <tr>
        <td>Occurrences</td>
        <td>
<?php
        if ($occurences > 0) {
            echo "<a href=\"objects.php?model=".$id."\">".$occurences;
            echo $occurences > 1 ? " objects" : " object";
            echo "</a>";
        } else {
            echo "0 object";
        }
?>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <a href="get_model_files.php?type=pack&amp;id=<?php echo $id; ?>">Download model</a> | <a href="app.php?c=UpdateModel&amp;a=modelUpdateForm&amp;modelId=<?php echo $id; ?>">Update model/info</a>
        </td>
    </tr>
    <tr>
        <td align="center" colspan="3" id="webglTd">
            <div id="webgl" style="resize: vertical; overflow: auto;">
                <a onclick="showWebgl()">Show 3D preview in WebGL</a>
            </div>
        </td>
    </tr>
</table>

<script type="text/javascript">
function showWebgl() {
    var objectViewer = document.createElement("object");
    objectViewer.width = "100%";
    objectViewer.height = "99%";
    objectViewer.data = "app.php?c=Models&a=modelViewer&id=<?php echo $id; ?>";
    objectViewer.type = "text/html";
    var webgl = document.getElementById("webgl");
    webgl.innerHTML = "";
    webgl.style.height = "500px";
    webgl.style.textAlign = "center";
    webgl.appendChild(objectViewer);
    document.getElementById("webglTd").innerHTML += "AC3D viewer powered by Hangar - Juan Mellado. Read <a href=\"http://en.wikipedia.org/wiki/Webgl\">here to learn about WebGL</a>.";
}
</script>

<?php
include 'view/footer.php';
?>
