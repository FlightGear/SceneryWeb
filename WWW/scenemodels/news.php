<?php
require_once 'autoload.php';
$modelDaoRO = \dao\DAOFactory::getInstance()->getModelDaoRO();
$newsPostDaoRO = \dao\DAOFactory::getInstance()->getNewsPostDaoRO();

require 'view/header.php';

if (isset($_REQUEST['offset']) && preg_match(FormChecker::$regex['pageoffset'], $_REQUEST['offset'])) {
    $offset = $_REQUEST['offset'];
}
else {
    $offset = 0;
}
?>

  <h1>FlightGear Scenery Database Latest News</h1>
  
<?php
    $pagesize = 10;

    $newsPosts = $newsPostDaoRO->getNewsPosts($offset, $pagesize);

    foreach ($newsPosts as $newsPost) {
        echo "<div class=\"paragraph_bloc\">\n" .
             "<div class=\"header\">\n" .
             "<div class=\"newsdate\">".$newsPost->getDate()->format("Y-m-d (H:i)")."</div>\n" .
             "<div class=\"newsnormal\">by</div>" .
             "<div class=\"newsauthor\"><a href=\"author.php?id=".$newsPost->getAuthor()->getId()."\">".$newsPost->getAuthor()->getName()."</a></div>" .
             "<div class=\"clear\"></div></div>\n" .
             "<div class=\"body\">".$newsPost->getText()."</div>\n" .
             "</div>\n";
    }
?>
  <table>  
    <tr class="bottom">
        <td colspan="9" align="center">
<?php 
            if ($offset >= 10) {
                echo "<a href=\"news.php?offset=".($offset-10)."\">&lt; Newer news</a> | ";
            }
?>
            <a href="news.php?offset=<?php echo $offset+10;?>">Older news &gt;</a>
        </td>
    </tr>
  </table>
<?php require 'view/footer.php';?>
