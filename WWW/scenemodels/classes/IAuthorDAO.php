<?php

interface IAuthorDAO {

    public function addAuthor($author);

    public function updateAuthor($author);
    
    public function getAuthor($authorId);
    
    public function getAllAuthors($offset, $pagesize);
}

?>
