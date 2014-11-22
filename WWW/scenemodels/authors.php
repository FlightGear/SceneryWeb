<?php
require_once 'autoload.php';
$authorDaoRO = DAOFactory::getInstance()->getAuthorDaoRO();

require 'inc/header.php';

if (isset($_REQUEST['offset']) && preg_match(FormChecker::$regex['pageoffset'],$_REQUEST['offset'])){
    $offset = $_REQUEST['offset'];
} else {
    $offset = 0;
}

$pagesize = 20;


?>

<h1>FlightGear Scenery Authors Directory</h1>
  
<table>
    <tr>
        <th>Author</th>
        <th>Comments of the author</th>
    </tr>
<?php
    $authors = $authorDaoRO->getAllAuthors($offset, $pagesize);
    
    foreach ($authors as $author){
        echo "<tr>" .
                 "<td style=\"width: 25%\">\n" .
                     "<b><a href=\"author.php?id=".$author->getId()."\">".$author->getName()."</a><b/>" .
                 "</td>" .
                 "<td>".$author->getDescription()."</td>" .
             "</tr>";
    }
?>
    <tr class="bottom">
        <td colspan="9" align="center">
<?php 
            if ($offset >= $pagesize) {
                echo "<a href=\"authors.php?offset=".($offset-$pagesize)."\">Prev</a> | ";
            }
?>
            <a href="authors.php?offset=<?php echo $offset+$pagesize;?>">Next</a>
        </td>
    </tr>
</table>
<?php require 'inc/footer.php';?>
