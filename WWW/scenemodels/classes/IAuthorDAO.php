<?php

/**
 * Interface for Author Data Access Object
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */

interface IAuthorDAO {

    public function addAuthor($author);

    public function updateAuthor($author);
    
    public function getAuthor($authorId);
    
    public function getAllAuthors($offset, $pagesize);
}

?>
