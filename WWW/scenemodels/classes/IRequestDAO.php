<?php

/**
 *
 * @author julien
 */
interface IRequestDAO {
    public function getRequest($id);
    
    public function saveRequest($request);
    
    public function getPendingRequests();
}
?>