<?php

// Including librairies
require_once ('inc/functions.inc.php');
include 'inc/header.php';

?>
<script type='text/javascript' src='https://www.google.com/jsapi'></script>
<script type='text/javascript'>
    google.load('visualization', '1', {'packages': ['geochart','corechart']});
    google.setOnLoadCallback(drawRegionsMap);

    var regionId = 'world';
    var worldmap = 'data1';

    function drawRegionsMap() {
        if (arguments[0]!= 'auto' && arguments[0]) {
            regionId = arguments[0];
        }
        if (arguments[1] != 'auto' && arguments[1]) {
            worldmap = arguments[1];
        }
        var data1 = google.visualization.arrayToDataTable([
            ['Country', 'Object density'],
            <?php
            $resource_r = connect_sphere_r();

            // Preprocessing signs and models and objects, as they are used later on.
            $models = count_models();
            $objects = count_objects();

            $result = pg_query($resource_r, "SELECT count(si_id) AS count FROM fgs_signs;");
            $row    = pg_fetch_assoc($result);
            $signs  = $row["count"];

            $query = "SELECT COUNT(ob_id) AS count, COUNT(ob_id)/(SELECT shape_sqm/10000000000 FROM gadm2_meta WHERE iso ILIKE co_three) AS density, co_name, co_three " .
                     "FROM fgs_objects, fgs_countries " .
                     "WHERE ob_country = co_code AND co_three IS NOT NULL " .
                     "GROUP BY co_code " .
                     "HAVING COUNT(ob_id)/(SELECT shape_sqm FROM gadm2_meta WHERE iso ILIKE co_three) > 0 " .
                     "ORDER BY count DESC ";
            $result = pg_query($resource_r, $query);

            $list = "";
            while ($row = pg_fetch_assoc($result)) {
                $country = rtrim($row['co_name']);
                $list .= "[\"".$country."\", ".round($row['density'])."],\n ";
            }
            echo $list;
            ?>
        ]);
        var data2 = google.visualization.arrayToDataTable([
            ['Country', 'Objects'],
            <?php
            pg_result_seek($result,0);
            $list = "";
            while ($row = pg_fetch_assoc($result)) {
                $country = rtrim($row['co_name']);
                $list .= "[\"".$country."\", ".$row['count']."],\n";
            }
            echo $list;
            ?>
        ]);

        var options = {
            backgroundColor: '#ADCDFF',
            keepAspectRatio: false
        };
        if (regionId != '[object Event]') {
            options['region'] = regionId;
        }
        var map = new google.visualization.GeoChart(document.getElementById('map_div'));
        google.visualization.events.addListener(map, "error", function errorHandler(e) {
            google.visualization.errors.removeError(e.id);
        });

        if (worldmap === "data2") {
            map.draw(data2, options);
        }
        if (worldmap === "data1") {
            map.draw(data1, options);
        }
    };

    google.setOnLoadCallback(drawChart);
    function drawChart() {
        var dataPie = google.visualization.arrayToDataTable([
            ['Country', 'Objects'],
            <?php
            pg_result_seek($result,0);

            $list = "";
            while ($row = pg_fetch_assoc($result)) {
                $list .= "[\"".rtrim($row['co_name'])."\", ".$row['count']."],\n";
            }
            echo $list;
            ?>
        ]);
        var dataPieAuthors = google.visualization.arrayToDataTable([
            ['Author', 'Objects'],
            <?php
            $query = "SELECT COUNT(mo_id) AS count, au_name " .
                     "FROM fgs_models, fgs_authors " .
                     "WHERE mo_author = au_id " .
                     "GROUP BY au_id " .
                     "ORDER BY count DESC";
            $resultAuthors = pg_query($resource_r, $query);

            $list = "";
            while ($row = pg_fetch_assoc($resultAuthors)) {
                $list .= "['".$row['au_name']."', ".$row['count']."],\n";
            }
            echo $list;
            ?>
        ]);

        var optionsPie = {
            chartArea: {height:"100%"},
            backgroundColor: 'none',
            pieSliceBorderColor: 'none',
            slices: {20: {color: '#ccc'}},
            sliceVisibilityThreshold: 1/100,
            legend: { alignment: 'center' }
        };

        var chartPie = new google.visualization.PieChart(document.getElementById('chart_pie_div'));
        google.visualization.events.addListener(chartPie, 'select', function () {
            // GeoChart selections return an array of objects with a row property; no column information
            var selection = chartPie.getSelection();
            dataPie.removeRow(selection[0].row);
            chartPie.draw(dataPie, optionsPie);
        });
        chartPie.draw(dataPie, optionsPie);

        var chartPieAuthors = new google.visualization.PieChart(document.getElementById('chart_pie_authors_div'));
        google.visualization.events.addListener(chartPieAuthors, 'select', function () {
            // GeoChart selections return an array of objects with a row property; no column information
            var selection = chartPieAuthors.getSelection();
            dataPieAuthors.removeRow(selection[0].row);
            chartPieAuthors.draw(dataPieAuthors, optionsPie);
        });
        chartPieAuthors.draw(dataPieAuthors, optionsPie);
    };

    google.setOnLoadCallback(drawBars);
    
    function drawBars(sorting) {
        var dataBarCountry = google.visualization.arrayToDataTable([
            ['Country', 'Object density', 'Objects'],
            <?php
            pg_result_seek($result,0);
            $i = 0;
            $list = "";
            while ($row = pg_fetch_assoc($result) and $i < 20) {
                $country = rtrim($row['co_name']);
                if ($country != "Unknown") {
                    $list .= "[\"".$country."\", ".round($row['density']).", ".$row['count']."],\n";
                    $i++;
                }
            }
            echo $list;
            ?>
        ]);
        if (sorting != "[object Event]") {
            if (sorting) {
                dataBarCountry.sort([{column: 2,desc: true},{column: 1,desc: true}]);
            } else {
                dataBarCountry.sort([{column: 1,desc: true},{column: 2,desc: true}]);
            }
        }

        var optionsBarCountry = {
            series:{0:{targetAxisIndex:0},1:{targetAxisIndex:1}},
            vAxes: {
                0: {
                    color: 'blue',
                    title: 'Object density (objects per 10,000 sq. km)',
                    baseline: <?php echo (($objects/148940000)*10000); ?>,
                    baselineColor: 'blue'
                },
                1: {
                    color: 'red',
                    title: 'Objects',
                    logScale: true
                }
            },
            backgroundColor: 'none',
            chartArea: {top: 35, height: 350, width: '75%'},
            hAxis: { slantedTextAngle: 50},
            focusTarget: 'category'
        };

        var chartBarCountry = new google.visualization.ColumnChart(document.getElementById('chart_bar_country_div'));
        chartBarCountry.draw(dataBarCountry, optionsBarCountry);
    };

    function drawVisualization() {
        // Create and populate the data table.
        var dataObjects = new google.visualization.DataTable();
        dataObjects.addColumn('date', 'Date');
        dataObjects.addColumn('number', 'Objects');
        dataObjects.addColumn('number', 'Models');
        dataObjects.addColumn('number', 'Signs');

        dataObjects.addRows([
            [new Date(2008,3,8), 993836, 735, 0],
            [new Date(2008,5,1), 994057, 786, 0],
            [new Date(2008,09,15), 1038108, 1269, 573 ],
            [new Date(2008,11,5), 1038477, 1306, 679 ],
            [new Date(2009,0,7), 1036978, 1340, 723 ],
            [new Date(2009,1,1), 1113318, 1371, 723],
            [new Date(2009,2,6), 1113341, 1392, 723 ],
            [new Date(2009,5,6), 1113531, 1501, 723 ],
            [new Date(2009,10,4), 1115669, 1621, 967 ],
            [new Date(2010,2,9), 1117244, 1725, 968 ],
            [new Date(2010,3,30), 1117270, 1729, 1235 ],
            [new Date(2010,5,30), 1117391, 1752, 1593 ],
            [new Date(2010,10,3), 1120218, 1931, 1743 ],
            [new Date(2011,0,20), 1120390, 2009, 1743 ],
            [new Date(2011,3,12), 1121693, 2112, 1900 ],
            [new Date(2011,5,9), 1122199, 2213, 1974 ],
            [new Date(2011,10,1), 1122439, 2324, 1974 ],
            [new Date(2012,0,4), 1122980, 2390, 2013 ],
            [new Date(2012,0,15), 1123543, 2400, 2013 ],
            [new Date(2012,1,24), 1123673, 2434, 2051 ],
            [new Date(2012,4,4), 1123722, 2477, 2074 ],
            [new Date(2012,6,18), 1123988, 2523, 2074 ],
            [new Date(2012,6,26), 1124686, 2523, 2074 ],
            [new Date(2012,7,21), 1106060, 2605, 2074 ],
            [new Date(2012,7,22), 1106125, 2605, 2074 ],
            [new Date(2012,7,25), 1106276, 2617, 2074 ],
            [new Date(<?php echo date('Y').",".(date('n')-1).",".date('j')."), ".$objects.", ".$models.", ".$signs; ?> ]
        ]);

        // Create and draw the visualization.
        new google.visualization.LineChart(document.getElementById('chart_objects_div')).
        draw(dataObjects, {
            series:{0:{targetAxisIndex:0},1:{targetAxisIndex:1},2:{targetAxisIndex:1}},
            vAxes: {
                0: {
                    color: 'blue',
                    title: 'Objects'
                },
                1: {
                    color: 'red',
                    title: 'Models and signs'
                }
            },
            pointSize: 5,
            backgroundColor: 'none',
            chartArea: {top: 35, height: 430},
            focusTarget: 'category'
            }
        );
    };

    google.setOnLoadCallback(drawVisualization);
</script>

<h1>FlightGear Scenery Statistics</h1>
<?php

echo "<p class=\"center\">The database currently contains <a href=\"models.php\">".number_format($models, '0', '', ' ')." models</a> placed in the scenery as <a href=\"objects.php\">".number_format($objects, '0', '', ' ')." seperate objects</a>, plus ".number_format($signs, '0', '', ' ')." taxiway signs.</p>\n";
?>
    <table class="float">
        <tr><th colspan="2">Recently updated objects</th></tr>
<?php
        $query = "SELECT ob_id, ob_text, to_char(ob_modified,'YYYY-mm-dd (HH24:MI)') AS ob_datedisplay " .
                 "FROM fgs_objects " .
                 "ORDER BY ob_modified DESC " .
                 "LIMIT 10";
        $result = pg_query($query);
        while ($row = pg_fetch_assoc($result)) {
            echo "<tr>\n" .
                    "<td><a href=\"objectview.php?id=".$row["ob_id"]."\">".$row["ob_text"]."</a></td>\n" .
                    "<td>".$row["ob_datedisplay"]."</td>\n" .
                 "</tr>\n";
        }
?>
    </table>
    <table class="float">
        <tr><th colspan="2">Recently updated models</th></tr>
<?php
        $query = "SELECT mo_id, mo_name, to_char(mo_modified,'YYYY-mm-dd (HH24:MI)') AS mo_datedisplay " .
                 "FROM fgs_models " .
                 "ORDER BY mo_modified DESC " .
                 "LIMIT 10";
        $result = pg_query($query);
        while ($row = pg_fetch_assoc($result)){
            echo "<tr>\n" .
                    "<td><a href=\"modelview.php?id=".$row["mo_id"]."\">".$row["mo_name"]."</a></td>\n" .
                    "<td>".$row["mo_datedisplay"]."</td>\n" .
                "</tr>\n";
        }
?>
    </table>
    
    <div class="clear"></div><br/>
    
    <table class="float">
        <tr><th>Objects by country</th></tr>
        <tr><td>Click a country to remove it from the pie.</td></tr>
        <tr><td><div id="chart_pie_div" style="width: 100%; height: 250px;"></div></td></tr>
    </table>
    <table class="float">
        <tr><th>Models by author</th></tr>
        <tr><td>Click an author to remove him from the pie.</td></tr>
        <tr><td><div id="chart_pie_authors_div" style="width: 100%; height: 250px;"></div></td></tr>
    </table>

    <div class="clear"></div><br/>

    <table>
        <tr>
            <td width="80%" style="border: 0px;">
                <div id="map_div" style="width: 100%; height: 500px;"></div>
            </td>
            <td valign="top" style="border: 0px;">
                <b>Show:</b>
                <ul>
                    <li><a onclick="drawRegionsMap('auto','data1')">Object density</a><br/>(objects / 10,000 sq. km)</li>
                    <li><a onclick="drawRegionsMap('auto','data2')">Absolute object count</a></li>
                </ul>
                <b>Zoom in to:</b>
                <ul>
                    <li><a onclick="drawRegionsMap('002','auto')">Africa</a></li>
                    <li><a onclick="drawRegionsMap('142','auto')">Asia</a></li>
                    <li><a onclick="drawRegionsMap('029','auto')">Carribean</a></li>
                    <li><a onclick="drawRegionsMap('150','auto')">Europe</a></li>
                    <li><a onclick="drawRegionsMap('021','auto')">Northern America</a></li>
                    <li><a onclick="drawRegionsMap('013','auto')">Central America</a></li>
                    <li><a onclick="drawRegionsMap('005','auto')">South America</a></li>
                    <li><a onclick="drawRegionsMap('009','auto')">Oceania</a></li><br/>
                    <li><a onclick="drawRegionsMap('world','auto')">Reset view</a></li>
                </ul>
            </td>
        </tr>
    </table>

    <div class="clear"></div><br/>

    <table>
        <tr>
            <td width="80%" style="border: 0px;">
                <div id="chart_bar_country_div" style="width: 100%; height: 500px;"></div>
            </td>
            <td valign="top" style="border: 0px;">
                <p>The blue horizontal line indicates the world average object density.</p>
                <b>Sort by:</b>
                <ul>
                    <li><a onclick="drawBars(false)">Object density</a><br/>(objects / 10,000 sq. km)</li>
                    <li><a onclick="drawBars(true)">Absolute object count</a></li>
                </ul>
            </td>
        </tr>
    </table>

    <div class="clear"></div><br/>

    <table>
        <tr><th>Time evolution</th></tr>
        <tr>
            <td>
                <div id="chart_objects_div" style="width: 100%; height: 500px;"></div>
            </td>
        </tr>
    </table>

    <div class="clear"></div>

<?php include 'inc/footer.php';?>
