<?php

/**
 * Interface for Model Files
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */

interface IModelFiles {

    public function getPackage();

    public function getACFile();
    
    /**
     * Return XML file content, or null if there is no XML
     */
    public function getXMLFile();
    
    public function getTexturesNames();
    
    public function getTexture($filename);
}

?>