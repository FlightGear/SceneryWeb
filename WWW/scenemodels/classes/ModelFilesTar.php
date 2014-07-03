<?php

require_once dirname(__FILE__) . '/../inc/functions.inc.php';
require_once 'IModelFiles.php';

/**
 * Model files in a TAR format
 *
 * @author     Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2014 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */

class ModelFilesTar implements IModelFiles {

    private $modelfile;

    public function __construct($modelfile) {
        $this->modelfile = $modelfile;
    }
    
    public function getPackage() {
        return $this->modelfile;
    }

    public function getACFile() {
        $targetPath = open_tgz($this->modelfile);
        $dir = opendir($targetPath);
        $content = null;

        while ($file = readdir($dir)) {
            if (show_file_extension($file) == "ac") {
                $filepath = $targetPath."/".$file;
                $content = file_get_contents($filepath);
                break;
            }
        }
        
        close_tgz($targetPath);
        
        return $content;
    }
    
    public function getXMLFile() {
        $targetPath = open_tgz($this->modelfile);
        $dir = opendir($targetPath);
        $content = null;
        
        while ($file = readdir($dir)) {
            if (show_file_extension($file) == "xml") {
                $filepath = $targetPath."/".$file;
                $content = file_get_contents($filepath);
                break;
            }
        }
        
        close_tgz($targetPath);
        
        return $content;
    }
    
    public function getTexturesNames() {
        $targetPath = open_tgz($this->modelfile);
        $dir = opendir($targetPath);
        
        $names = array();
        
        while ($filename = readdir($dir)) {
            $extension = show_file_extension($filename);
            if ($extension == "png" || $extension == "rgb") {
                $names[] = $filename;
            }
        }
        
        close_tgz($targetPath);
        
        return $names;
    }
    
    public function getTexture($filename) {
        $targetPath = open_tgz($this->modelfile);
        $dir = opendir($targetPath);

        while ($file = readdir($dir)) {
            if ($file == $filename) {
                $filepath = $targetPath."/".$file;
                $content = file_get_contents($filepath);
                break;
            }
        }
        
        close_tgz($targetPath);
        
        return $content;
    }

}

?>