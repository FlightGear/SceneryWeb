<?php include 'inc/header.php';?>

<script type="text/javascript">
function popmap(lat,lon,zoom) {
    popup = window.open("http://mapserver.flightgear.org/popmap?zoom="+zoom+"&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
    popup.focus();
}
</script>

<?php
if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id']))) {
    $id = $_REQUEST['id'];
    $result = pg_query("SELECT mo_author, mo_id, mo_modified, mo_name, mo_notes, mo_path, mo_shared, to_char(mo_modified,'YYYY-mm-dd (HH24:MI)') AS mo_datedisplay FROM fgs_models WHERE mo_id=$id;");
    $model = pg_fetch_assoc($result);
}

echo "<h1>".$model["mo_name"]."</h1>";
if (!empty($model["mo_notes"])) {
    echo "<p>".$model["mo_notes"]."</p>";
}
?>
<table>
    <tr>
        <td style="width: 320px" rowspan="7"><img src="modelthumb.php?id=<?php if (isset($model["mo_id"])) print $model["mo_id"]; ?>" alt=""/></td>
<?php
        if ($model["mo_shared"] != 0) print "<td>Path</td>"; else print "<td>File name</td>";
?>
        <td>
<?php
            $result = pg_query("SELECT mg_id, mg_name, mg_path FROM fgs_modelgroups WHERE mg_id = '$model[mo_shared]';");
            $row = pg_fetch_assoc($result);
            if ($model["mo_shared"] != 0) print "Models/".$row["mg_path"]."".$model["mo_path"]; else print $model["mo_path"];
        print "</td>";
?>
    </tr>
    <tr>
        <td>Type</td>
        <td>
<?php
            print "<a href=\"modelbrowser.php?shared=".$model["mo_shared"]."\">".$row["mg_name"]."</a>";
?>
        </td>
    </tr>
    <tr>
        <td>Author</td>
        <td>
            <?php
                $result = pg_query("SELECT au_id, au_name FROM fgs_authors WHERE au_id = '$model[mo_author]';");
                $row = pg_fetch_assoc($result);
                print "<a href=\"author.php?id=".$model["mo_author"]."\">".$row["au_name"]."</a>";
            ?>
        </td>
    </tr>
    <tr>
        <td>Last updated</td>
        <td><?php print $model["mo_datedisplay"]; ?></td>
    </tr>
    <tr>
        <td>Model ID</td>
        <td><?php print $id; ?></td>
    </tr>
    <?php
    if ($model["mo_shared"] == 0) {
        $result = pg_query("SELECT ob_id FROM fgs_objects WHERE ob_model = '$id';");
        $row = pg_fetch_assoc($result);
        ?>
        <tr>
            <td>Corresponding object ID</td>
            <td><a href="objectview.php?id=<?php echo $row["ob_id"]."\">".$row["ob_id"]; ?></a></td>
        </tr>
    <?php } else {
        $query = "SELECT COUNT(*) AS number " .
                 "FROM fgs_objects " .
                 "WHERE ob_model=$id";
        $numbers = pg_query($query);
        $number = pg_fetch_assoc($numbers);
        $occurences = $number["number"];
        echo "<tr>" .
                "<td>Occurrences</td>" .
                "<td>";
            if ($occurences > 0) {
                echo "<a href=\"objects.php?model=".$id."\">".$occurences;
                echo $occurences > 1 ? " objects" : " object";
                echo "</a>";
            } else {
                echo "0 objects";
            }
        echo "</tr>";
    }
    ?>
    <tr>
        <td colspan="2">
            <a href="modelfile.php<?php if (isset($id)) print "?id=".$id; ?>">Download model</a>
        </td>
    </tr>
    <tr>
        <td align="center" colspan="3" id="webglTd">
            <div id="webgl" style="resize: vertical; overflow: auto;">
                <a onclick="showWebgl()">Show 3D preview in WebGL</a>
            </div>
        </td>
    </tr>
    <?php
    if ($model["mo_shared"] == 0) {
        $query = "SELECT ST_Y(wkb_geometry) AS ob_lat, " .
                 "ST_X(wkb_geometry) AS ob_lon " .
                 "FROM fgs_objects " .
                 "WHERE ob_model=$id";
        $chunks = pg_query($query);
        $chunk = pg_fetch_assoc($chunks);
        $lat = floor($chunk["ob_lat"]/10)*10;
        $lon = floor($chunk["ob_lon"]/10)*10;
    ?>
        <tr>
            <td align="center" colspan="3">
                <div id="map" style="resize: vertical; overflow: auto;">
                    <a onclick="showMap()">Show location on map</a>
                </div>
            </td>
        </tr>
        <script type="text/javascript">
        function showMap() {
            var objectMap = document.createElement("object");
            objectMap.width = "100%";
            objectMap.height = "99%";
            objectMap.data = "http://mapserver.flightgear.org/popmap/?zoom=13&lat=<?php echo $chunk["ob_lat"]; ?>&lon=<?php echo $chunk["ob_lon"]; ?>";
            objectMap.type = "text/html";
            var map = document.getElementById("map");
            map.innerHTML = "";
            map.style.height = "500px";
            map.style.textAlign = "center";
            map.appendChild(objectMap);
        }
        </script>
    <?php
    }
    ?>
</table>

<script type="text/javascript">
function showWebgl() {
    var objectViewer = document.createElement("object");
    objectViewer.width = "100%";
    objectViewer.height = "99%";
    objectViewer.data = "viewer.php?id=<?php echo $id; ?>";
    objectViewer.type = "text/html";
    var webgl = document.getElementById("webgl");
    webgl.innerHTML = "";
    webgl.style.height = "500px";
    webgl.style.textAlign = "center";
    webgl.appendChild(objectViewer);
    document.getElementById("webglTd").innerHTML += "AC3D viewer powered by Hangar - Juan Mellado. Read <a href=\"http://en.wikipedia.org/wiki/Webgl\">here to learn about WebGL</a>."
}
</script>

<?php include 'inc/footer.php'; ?>
