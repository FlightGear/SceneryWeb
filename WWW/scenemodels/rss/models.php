<?php 
  $link = pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass);
  header('Content-type: application/rss+xml');
  $query = "SELECT to_char(mo_modified,'Dy, DD Mon YYYY HH24:MM') AS modtime ";
  $query.= "FROM fgs_models ";
  $query.= "ORDER BY mo_modified DESC";
  $query.= "LIMIT 1";
  $result = pg_query($query);
  $row = pg_fetch_assoc($result);
  echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n";
?>
<rss version="2.0">
  <channel>
    <title>FGFSDB Model Updates</title>
    <link>http://scenemodels.flightgear.org/models.php</link>
    <language>en-GB</language>
    <copyright>Jon Stockill 2006-2008.</copyright>
    <description>FlightGear scenery object database model additions.</description>
    <ttl>720</ttl>
    <lastBuildDate><?php echo $row["modtime"];?> +0000</lastBuildDate>
    <?php
      $query = "SELECT to_char(mo_modified,'Dy, DD Mon YYYY HH24:MM') AS timestamp,mo_id,mo_name ";
      $query.= "FROM fgs_models ";
      $query.= "ORDER BY mo_modified DESC";
      $result = pg_query($query);
      while ($row = pg_fetch_assoc($result)){
    ?>
    <item>
      <link>http://scenemodels.flightgear.org/modeledit.php?id=<?php echo urlencode($row["mo_id"])?></link>
      <title><![CDATA[<?php echo $row["mo_name"]?> ]]></title> 
      <description><![CDATA[<?php echo $row["mo_name"]?> ]]></description> 
      <pubDate><?php echo $row["timestamp"]?> +0000</pubDate>
    </item>
    <?php
      }
    ?>
  </channel>
</rss>
