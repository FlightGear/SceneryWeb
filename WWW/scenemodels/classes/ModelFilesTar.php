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
        $target_path = open_tgz($this->modelfile);
        $dir = opendir($target_path);

        while (false !== ($file = readdir($dir))) {
            if (show_file_extension($file) == "ac") {
                $filepath = $target_path."/".$file;
                $content = file_get_contents($filepath);
                break;
            }
        }
        
        close_tgz($target_path);
        
        return $content;
    }
    
    public function getXMLFile() {
        $target_path = open_tgz($this->modelfile);
        $dir = opendir($target_path);
        
        while (false !== ($file = readdir($dir))) {
            if (show_file_extension($file) == "xml") {
                $filepath = $target_path."/".$file;
                $content = file_get_contents($filepath);
                break;
            }
        }
        
        close_tgz($target_path);
        
        return $content;
    }
    
    public function getTexturesNames() {
        $target_path = open_tgz($this->modelfile);
        $dir = opendir($target_path);
        
        $names = array();
        
        while (false !== ($filename = readdir($dir))) {
            $extension = show_file_extension($filename);
            if ($extension == "png" || $extension == "rgb") {
                $names[] = $filename;
                break;
            }
        }
        
        return $names;
    }
    
    public function getTexture($filename) {
        $target_path = open_tgz($this->modelfile);
        $dir = opendir($target_path);

        while (false !== ($file = readdir($dir))) {
            if ($file == $filename) {
                $filepath = $target_path."/".$file;
                $content = file_get_contents($filepath);
                break;
            }
        }
        
        close_tgz($target_path);
        
        return $content;
    }

}

?>
