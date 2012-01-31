<?php 
        $link=pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass);        
	header('Content-type: application/rss+xml');
	$result=pg_query("select to_char(ne_timestamp,'Dy, DD Mon YYYY HH24:MM') as updatetimestamp from fgs_news order by ne_timestamp desc limit 1;");
	$row = pg_fetch_assoc($result);
	print "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n";
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

$result=pg_query("select to_char(ne_timestamp,'Dy, DD Mon YYYY HH24:MM') as timestamp,ne_timestamp,ne_text,au_name from fgs_news,fgs_authors where au_id=ne_author order by ne_timestamp desc limit 10;");
while ($row = pg_fetch_assoc($result))
{
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
