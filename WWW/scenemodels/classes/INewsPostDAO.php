<?php

interface INewsPostDAO {

    public function addNewsPost($newsPost);

    public function updateNewsPost($newsPost);
    
    public function getNewsPost($newsPostId);
    
    public function getNewsPosts($offset, $pagesize);
}

?>
