<?php

/**
 * Interface for Request Data Access Object
 * 
 * @author Julien Nguyen
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
     * Gets request using signature
     * @param text $sig signature of the request to get
     * @return Request request
     */
    public function getRequestFromSig($sig);
    
    /**
     * Save request
     * @param Request $request request to save
     */
    public function saveRequest($request);
    
    public function deleteRequest($id);
    
    public function getPendingRequests();
}
?>