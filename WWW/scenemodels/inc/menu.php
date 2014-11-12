<script type="text/javascript" src="/inc/js/menu.js"></script>

<ul id="csstopmenu">
    <li class="mainitems" style="border-left-width: 1px">
        <div class="headerlinks"><a href="/">Home</a></div>
        <ul class="submenus">
            <li><a href="/news.php">News</a></li>
        </ul>
    </li>
    <li class="mainitems">
        <div class="headerlinks"><a href="/contribute.php">Contribute</a></div>
        <ul class="submenus">
            <li><a href="/submission/object/index.php">Add a new object position</a></li>
            <li><a href="/submission/object/index_mass_import.php">Massive import of objects</a></li>
            <li><a href="/submission/object/index_delete.php">Delete an object position</a></li>
            <li><a href="/submission/object/index_update.php">Update object geodata</a></li>
            <li class="separator"></li>
            <li><a href="/submission/model/index_model_add.php">Add a new model</a></li>
            <li><a href="/submission/model/index_model_update.php">Update a model</a></li>
        </ul>
    </li>
    <li class="mainitems">
        <div class="headerlinks"><a href="/models.php">Models</a></div>
        <ul class="submenus">
            <li><a href="/modelbrowser.php">Browse all</a></li>
<?php
            $modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
            $groups = $modelDaoRO->getModelsGroups();          
            
            foreach ($groups as $group) {
                $name = preg_replace('/&/',"&amp;", $group->getName());
                $name = preg_replace('/ /',"&nbsp;", $name);
                echo "<li><a href=\"/modelbrowser.php?shared=".$group->getId()."\">".$name."</a></li>\n";
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
        <div class="headerlinks"><a href="/mapserver.php">Mapserver</a></div>
        <ul class="submenus">
            <li><a href="http://mapserver.flightgear.org/shpdl/">Download shapefiles</a></li>
        </ul>
    </li>
    <li class="mainitems">
        <div class="headerlinks"><a href="/download.php">Download</a></div>
        <ul class="submenus">
            <li><a href="/objects_download.php">Download latest scenery objects</a></li>
            <li><a href="/scenery_download.php">Download scenery objects &amp; terrain</a></li>
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