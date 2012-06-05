<?php $link=pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable'); ?>

<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="style.css" type="text/css">
  </head>
  <body>

  <script type="text/javascript">
    // CSS Top Menu- By JavaScriptKit.com (http://www.javascriptkit.com)
    // Adopted from SuckerFish menu
    // For this and over 400+ free scripts, visit JavaScript Kit- http://www.javascriptkit.com/
    // Please keep this credit intact

    startMenu = function() {
      if (document.all&&document.getElementById) {
        cssmenu = document.getElementById("csstopmenu");

        for (i=0; i<cssmenu.childNodes.length; i++) {
          node = cssmenu.childNodes[i];

          if (node.nodeName=="LI") {
            node.onmouseover=function() {
              this.className+=" over";
            }
            node.onmouseout = function() {
              this.className=this.className.replace(" over", "");
            }
          }
        }
      }
    }

    if (window.attachEvent){
      window.attachEvent("onload", startMenu);
    }else{
      window.onload=startMenu;
    }
  </script>

  <table>
    <tr>
      <td background="/img/titleback.jpg">
        <img src="/img/fglogosm.jpg" alt="Logo Flightgear"/>
      </td>
    </tr>
  </table>

  <ul id="csstopmenu">
    <li class="mainitems" style="border-left-width: 1px">
      <div class="headerlinks"><a href="./">Home</a></div>
    </li>
    <li class="mainitems">
      <div class="headerlinks"><a href="contribute.php">Contribute</a></div>
      <ul class="submenus">
        <li><a href="submission/shared/index.php">Add a new shared object position.</a></li>
        <li><a href="submission/shared/index_update.php">Edit an existing shared object settings.</a></li>
        <li><a href="submission/shared/index_delete.php">Delete an existing shared object position.</a></li>
      </ul>
    </li>
    <li class="mainitems">
      <div class="headerlinks"><a href="models.php">Models</a></div>
      <ul class="submenus">
        <li><a href="modelbrowser.php">Browse All</a></li>
        <?php
          $query = "SELECT mg_id,mg_name ";
          $query.= "FROM fgs_modelgroups ";
          $query.= "ORDER BY mg_id";
          $result=pg_query($query);
          while ($row = pg_fetch_assoc($result)){
            $name=preg_replace('/ /',"&nbsp;",$row["mg_name"]);
            echo "<li><a href=\"/modelbrowser.php?shared=".$row["mg_id"]."\">".$name."</a></li>\n";
          }
        ?>
      </ul>
    </li>
    <li class="mainitems">
      <div class="headerlinks"><a href="/objects.php">Objects</a></div>
    </li>
    <li class="mainitems">
      <div class="headerlinks"><a href="/authors.php">Authors</a></div>
    </li>
    <li class="mainitems">
      <div class="headerlinks"><a href="/download/">Download</a></div>
      <ul class="submenus">
        <li><a href="/download/GlobalObjects.tgz">Global objects</a></li>
        <li><a href="/download/SharedModels.tgz">Shared models</a></li>
      </ul>
    </li>
    <li class="mainitems">
      <div class="headerlinks"><a href="/stats.php">Statistics</a></div>
      <ul class="submenus">
        <li><a href="/coverage.php">Coverage</a></li>
      </ul>
    </li>
    <li class="mainitems">
      <div class="headerlinks"><a href="/rss/">RSS</a></div>
    </li>
  </ul>

  <div id="clearmenu" style="clear: left"></div>
  <!-- <h3><font color="red">Service limited due to planned database maintenance.</font></h3> -->
