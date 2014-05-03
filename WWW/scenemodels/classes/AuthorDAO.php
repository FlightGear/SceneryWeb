<?php
require_once 'PgSqlDAO.php';
require_once 'IAuthorDAO.php';
require_once 'Author.php';

/**
 * Author Data Access Object implementation for PostgreSQL
 *
 * Database layer to access authors from PostgreSQL database
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */
 
class AuthorDAO extends PgSqlDAO implements IAuthorDAO {
    
    public function addAuthor($author) {
        //TODO
    }

    public function updateAuthor($author) {
        //TODO
    }
    
    public function getAuthor($authorId) {
        $result = $this->database->query("SELECT au_id, au_name, au_email, au_notes FROM fgs_authors WHERE au_id=$authorId;");
        $authorRow = pg_fetch_assoc($result);
        
        return $this->getAuthorFromRow($authorRow);
    }
    
    public function getAllAuthors($offset, $pagesize) {
        $result = $this->database->query("SELECT au_id, au_name, au_email, au_notes FROM fgs_authors ".
                                         "ORDER BY au_name LIMIT $pagesize OFFSET ".$offset);
        
        $resultArray = array();
                           
        while ($row = pg_fetch_assoc($result)) {
            $resultArray[] = $this->getAuthorFromRow($row);
        }
        
        return $resultArray;
    }
    
    private function getAuthorFromRow($authorRow) {
        $author = new Author();
        $author->setId($authorRow["au_id"]);
        $author->setName($authorRow["au_name"]);
        $author->setEmail($authorRow["au_email"]);
        $author->setDescription($authorRow["au_notes"]);
        
        return $author;
    }
}
?>
