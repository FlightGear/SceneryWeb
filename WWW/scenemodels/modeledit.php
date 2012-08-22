<?php include 'inc/header.php';?>

<script type="text/javascript">
function popmap(lat,lon,zoom) {
    popup = window.open("http://mapserver.flightgear.org/popmap?zoom="+zoom+"&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
    popup.focus();
}
</script>

<h1>FlightGear Scenery Model Directory</h1>
<?php

if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id']))) {
    $id = $_REQUEST['id'];
    $result = pg_query("SELECT *, to_char(mo_modified,'YYYY-mm-dd (HH24:MI)') AS mo_datedisplay FROM fgs_models WHERE mo_id=$id;");
    $model = pg_fetch_assoc($result);
};
?>
<table>
<tr>
    <td rowspan="8" style="width: 320px"><img src="modelthumb.php?id=<?php if (isset($model["mo_id"])) print $model["mo_id"]; ?>" alt=""/></td>
    <td>Name</td>
    <td><?php if (isset($model["mo_name"])) print $model["mo_name"]; ?></td>
</tr>
<tr>
    <td>Path</td>
    <td><?php if (isset($model["mo_path"])) print $model["mo_path"]; ?></td>
</tr>
<tr>
    <td>Type</td>
    <td>
        <?php
            $result = pg_query("SELECT mg_id, mg_name FROM fgs_modelgroups WHERE mg_id = '$model[mo_shared]';");
            $row = pg_fetch_assoc($result);
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
    <td>Last Updated</td>
    <td><?php if (isset($model["mo_datedisplay"])) print $model["mo_datedisplay"]; ?></td>
</tr>
<tr>
    <td>Model-ID</td>
    <td><?php print "$id"; ?></td>
</tr>
<tr>
    <td colspan="2" align="center">
        <?php
        if ($model["mo_shared"] == 0) {
            $query = "SELECT ST_Y(wkb_geometry) AS ob_lat, ";
            $query.= "ST_X(wkb_geometry) AS ob_lon ";
            $query.= "FROM fgs_objects ";
            $query.= "WHERE ob_model=$id";
            $chunks = pg_query($query);
            while ($chunk = pg_fetch_assoc($chunks)) {
                $lat = floor($chunk["ob_lat"]/10)*10;
                $lon = floor($chunk["ob_lon"]/10)*10;
                print "<a href=\"javascript:popmap(".$chunk["ob_lat"].",".$chunk["ob_lon"].",13)\">Map</a>&nbsp;-&nbsp";
            }
        }
        ?>
        <a href="modelfile.php<?php if (isset($id)) print "?id=".$id; ?>">Download Model</a>
    </td>
</tr>
<tr>
    <td colspan="3"><?php if (!empty($model["mo_notes"])) print "<u>Comment:</u>&nbsp;".$model["mo_notes"]; ?></td>
</tr>
</table>
<?php include 'inc/footer.php'; ?>