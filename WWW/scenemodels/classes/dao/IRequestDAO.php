<?php

/**
 * Interface for Request Data Access Object
 * 
 * @author julien
 */

namespace dao;

interface IRequestDAO {
    /**
     * Gets request using id
     * @param int $id id of the request to get
     * @return Request request
     */
    public function getRequest($id);
    
    /**
     * 
     * @param Request $request request to save
     */
    public function saveRequest($request);
    
    public function deleteRequest($id);
    
    public function getPendingRequests();
}
?>