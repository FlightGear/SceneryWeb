<?php
require_once 'PgSqlDAO.php';
require_once 'INewsPostDAO.php';
require_once 'NewsPost.php';
require_once 'Author.php';

class NewsPostDAO extends PgSqlDAO  implements INewsPostDAO {

    public function addNewsPost($newsPost) {
        // TODO
    }

    public function updateNewsPost($newsPost) {
    
    }
    
    public function getNewsPost($newsPostId) {
        $result = $this->database->query("SELECT *, date_trunc('seconds',ne_timestamp) AS formdate ".
                                         "FROM fgs_news, fgs_authors WHERE ne_id=$newsPostId;");
        $row = pg_fetch_assoc($result);
        
        return $this->getNewsPostFromRow($row);
    }
    
    public function getNewsPosts($offset, $pagesize) {
        $result = $this->database->query("SELECT *".
                                         "FROM fgs_news, fgs_authors ".
                                         "WHERE au_id = ne_author ".
                                         "ORDER BY ne_timestamp DESC ".
                                         "LIMIT $pagesize OFFSET ".$offset);
        
        $result_array = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $result_array[] = $this->getNewsPostFromRow($row);
        }
        
        return $result_array;
    }
    
    private function getNewsPostFromRow($row) {
        $author = new Author();
        $author->setId($row["au_id"]);
        $author->setName($row["au_name"]);
        $author->setEmail($row["au_email"]);
        $author->setDescription($row["au_notes"]);
    
        $newsPost = new NewsPost();
        $newsPost->setId($row["ne_id"]);
        $newsPost->setDate(new DateTime($row["ne_timestamp"]));
        $newsPost->setAuthor($author);
        $newsPost->setText($row["ne_text"]);
        
        return $newsPost;
    }
}

?>
