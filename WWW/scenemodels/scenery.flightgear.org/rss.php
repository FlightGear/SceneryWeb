<?php
################################################
#                  RSS : News                  #
################################################
if($_GET['action'] == "news"){
  $link = pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass);        
  header('Content-type: application/rss+xml');
  $query = "SELECT to_char(ne_timestamp,'Dy, DD Mon YYYY HH24:MM') AS updatetimestamp ";
  $query.= "FROM fgs_news ";
  $query.= "ORDER BY ne_timestamp DESC ";
  $query.= "LIMIT 1";
  $result = pg_query($query);
  $row = pg_fetch_assoc($result);
  echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n";
?>
<rss version="2.0">
  <channel>
    <title>FGFSDB Updates</title>
    <link>http://scenemodels.flightgear.org/</link>
    <language>en-GB</language>
    <copyright>Jon Stockill 2006.</copyright>
    <description>FlightGear scenery object database updates.</description>
    <ttl>720</ttl>
    <lastBuildDate><?php echo $row["updatetimestamp"];?> +0000</lastBuildDate>
    <?php
      $query = "SELECT to_char(ne_timestamp,'Dy, DD Mon YYYY HH24:MM') AS timestamp,ne_timestamp,ne_text,au_name ";
      $query.= "FROM fgs_news,fgs_authors ";
      $query.= "WHERE au_id=ne_author ";
      $query.= "ORDER BY ne_timestamp DESC ";
      $query.= "LIMIT 10";
      $result = pg_query($query);
      while ($row = pg_fetch_assoc($result)){
    ?>
    <item>
      <link>http://scenemodels.flightgear.org/newsarticle.php?article=<?php echo urlencode($row["ne_timestamp"])?></link>
      <description><![CDATA[<?php echo $row["ne_text"]?> ]]></description> 
      <pubDate><?php echo $row["timestamp"]?> +0000</pubDate>
    </item>
    <?php
      }
    ?>
  </channel>
</rss>
<?php
################################################
#                 RSS : Models                 #
################################################
}elseif($_GET['action'] == "models"){
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
<?php
}else{
################################################
#                       RSS                    #
################################################
?>
<?php include("include/menu.php"); ?>
<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">RSS</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">

    <p>
      To ensure you don't miss anything the following RSS feeds are available:
      <ul>
        <li><a href="rss.php?action=news">News</a> FGFSDB site news</li>
        <li><a href="rss.php?action=models">Models</a> Recent model additions</li>
      </ul>
    </p>
    <br/>

  </div>

</div>
<?php
  include("include/footer.php");
}
?>
