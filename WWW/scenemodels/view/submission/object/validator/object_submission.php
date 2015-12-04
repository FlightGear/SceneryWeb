<?php

$pageTitle = "Automated Objects Pending Requests Form";
include 'view/header.php';
echo "<p class=\"center\">Request #". $request->getId()."</p>" .
     "<p class=\"center\">Email: ".$request->getContributorEmail()."</p>".
     "<p class=\"center\">Comment: ".htmlspecialchars($request->getComment())."</p>";

$sig = $request->getSig();

switch (get_class($request)) {
case "model\RequestObjectUpdate":
    $oldObject = $request->getOldObject();
    $newObject = $request->getNewObject();

    $oldObjPos = $oldObject->getPosition();
    $newObjPos = $newObject->getPosition();

    $newModelMD = $modelDaoRO->getModelMetadata($newObject->getModelId());
    $oldModelMD = $modelDaoRO->getModelMetadata($oldObject->getModelId());

    // Obtain old/current values
    echo "<table><tr><th></th><th>Old/current</th><th>New</th></tr>";

    echo "<tr";
    if ($oldObject->getDescription() != $newObject->getDescription()) {
        echo " style=\"background-color: rgb(255, 200, 0)\"";
    }
    echo "><td>Description</td><td>".htmlspecialchars($oldObject->getDescription())."</td><td>".htmlspecialchars($newObject->getDescription())."</td></tr>";
    echo "<tr";
    if ($oldModelMD->getId() != $newModelMD->getId()) {
        echo " style=\"background-color: rgb(255, 200, 0)\"";
    }
    echo "><td>Object's model</td><td>".htmlspecialchars($oldModelMD->getName())."</td><td>".htmlspecialchars($newModelMD->getName())."</td></tr>";
    echo "<tr><td>Thumbnail</td><td><img src='app.php?c=Models&amp;a=thumbnail&amp;id=".$oldModelMD->getId()."' alt=''/></td>".
            "<td><img src='app.php?c=Models&amp;a=thumbnail&amp;id=".$newModelMD->getId()."' alt=''/></td></tr>";
    echo "<tr";
    if ($oldObjPos->getLongitude() != $newObjPos->getLongitude()) {
        echo " style=\"background-color: rgb(255, 200, 0)\"";
    }
    echo "><td>Longitude</td><td>".$oldObjPos->getLongitude()."</td><td>".$newObjPos->getLongitude()."</td></tr>";
    echo "<tr";
    if ($oldObjPos->getLatitude() != $newObjPos->getLatitude()) {
        echo " style=\"background-color: rgb(255, 200, 0)\"";
    }
    echo "><td>Latitude</td><td>".$oldObjPos->getLatitude()."</td><td>".$newObjPos->getLatitude()."</td></tr>";
    echo "<tr";
    if ($oldObject->getCountry()->getCode() != $newObject->getCountry()->getCode()) {
        echo " style=\"background-color: rgb(255, 200, 0)\"";
    }
    echo "><td>Country</td><td>".$oldObject->getCountry()->getName()."</td><td>".$newObject->getCountry()->getName()."</td></tr>";

    echo "<tr style=\"background-color: rgb(255, 200, 0)\">";
    echo "<td>Elevation</td><td>".$oldObject->getGroundElevation()."</td><td>Will be recalculated</td></tr>";
    echo "<tr";
    if ($oldObject->getElevationOffset() != $newObject->getElevationOffset()) {
        echo " style=\"background-color: rgb(255, 200, 0)\"";
    }
    echo "><td>Elevation offset</td><td>".$oldObject->getElevationOffset()."</td><td>".$newObject->getElevationOffset()."</td></tr>";

    echo "<tr";
    if ($oldObject->getOrientation() != $newObject->getOrientation()) {
        echo " style=\"background-color: rgb(255, 200, 0)\"";
    }
    echo "><td>Heading (STG)</td><td>".\ObjectUtils::headingTrue2STG($oldObject->getOrientation())." (STG) - ".$oldObject->getOrientation()."(true)</td>".
         "<td>".\ObjectUtils::headingTrue2STG($newObject->getOrientation())." (STG) - ".$newObject->getOrientation()." (true)</td></tr>";

    echo "<tr><td>Map</td><td><object data=\"http://mapserver.flightgear.org/popmap/?lon=".$oldObjPos->getLongitude()."&amp;lat=".$oldObjPos->getLatitude()."&amp;zoom=14\" type=\"text/html\" width=\"100%\" height=\"240\"></object></td>".
         "<td><object data=\"http://mapserver.flightgear.org/popmap/?lon=".$newObjPos->getLongitude()."&amp;lat=".$newObjPos->getLatitude()."&amp;zoom=14\" type=\"text/html\" width=\"100%\" height=\"240\"></object></td></tr>" .
         "</tr>";
    break;

case "model\RequestObjectDelete":

    $objectToDel = $request->getObjectToDelete();
    $objDelPos = $objectToDel->getPosition();
    $modelMD = $modelDaoRO->getModelMetadata($objectToDel->getModelId());

    echo "<table><tr><th>Obj. ID</th><th>Longitude</th><th>Latitude</th><th>Country</th><th>Elevation</th><th>Elev. offset</th><th>True orientation</th><th>Model</th><th>Map</th></tr>";
    echo "<tr>" .
         "<td>".$objectToDel->getId()."</td>" .
         "<td>".$objDelPos->getLongitude()."</td>" .
         "<td>".$objDelPos->getLatitude()."</td>" .
         "<td>".$objectToDel->getCountry()->getName()."</td>" .
         "<td>".$objectToDel->getGroundElevation()."</td>" .
         "<td>".$objectToDel->getElevationOffset()."</td>" .
         "<td>".$objectToDel->getOrientation()."</td>" .
         "<td><a href=\"http://".$_SERVER['SERVER_NAME']."/app.php?c=Models&amp;a=view&amp;id=".$modelMD->getId()."\">".htmlspecialchars($modelMD->getName())."</a></td>" .
         "<td><a href=\"http://mapserver.flightgear.org/popmap/?lon=".$objDelPos->getLongitude()."&amp;lat=".$objDelPos->getLatitude()."&amp;zoom=14\">Map</a></td>" .
         "</tr>";
    break;
}
?>

    <tr>
        <td colspan="9" class="submit">
            <form action="app.php?c=ObjectValidator&amp;a=actionOnRequest" method="POST">
                <input type="hidden" name="sig" value="<?php echo $sig;?>"/>
                Comment : <input type="text" name="maintainer_comment" size="85" placeholder="Drop a comment to the submitter"/><br/>

                <input type="submit" name="accept" value="Accept" />
                <input type="submit" name="reject" value="Reject" />
            </form>
        </td>
    </tr>
</table>
<?php
    include 'view/footer.php';
