<?php

require_once dirname(__FILE__) . '/../inc/functions.inc.php';

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
    
    /**
     * Returns the extension of a file sent in parameter
     * @param string $filepath file path.
     * @return string extension.
     */
    private function showFileExtension($filepath) {
        return pathinfo($filepath, PATHINFO_EXTENSION);
    }

    /**
     * Extracts a tgz file into a temporary directory and returns its path.
     * @param type $archive
     * @return string
     */
    private function openTGZ($archive) {
        // Managing possible concurrent accesses on the maintainer side.
        $targetPath = sys_get_temp_dir() .'/submission_'.rand();

        while (file_exists($targetPath)) {
            usleep(500);    // Makes concurrent access impossible: the script has to wait if this directory already exists.
        }

        if (mkdir($targetPath)) {
            if (file_exists($targetPath) && is_dir($targetPath)) {
                $file = $targetPath.'/submitted_files.tar.gz';     // Defines the destination file
                file_put_contents($file, $archive);                // Writes the content of $file into submitted_files.tar.gz

                $detar_command = 'tar xvzf '.$targetPath.'/submitted_files.tar.gz -C '.$targetPath. '> /dev/null';
                system($detar_command);
            }
        } else {
            error_log("Impossible to create ".$targetPath." directory!");
        }

        return $targetPath;
    }
    
    /**
     * Close a temporary directory opened for a tgz file.
     * @param type $targetPath
     */
    private function closeTGZ($targetPath) {
        // Deletes compressed file
        unlink($targetPath.'/submitted_files.tar.gz');
        
        // Deletes temporary submission directory
        clear_dir($targetPath);
    }

    public function getACFile() {
        $targetPath = $this->openTGZ($this->modelfile);
        $dir = opendir($targetPath);
        $content = null;

        while ($file = readdir($dir)) {
            if ($this->showFileExtension($file) == 'ac') {
                $filepath = $targetPath."/".$file;
                $content = file_get_contents($filepath);
                break;
            }
        }
        
        $this->closeTGZ($targetPath);
        
        return $content;
    }
    
    public function getXMLFile() {
        $targetPath = $this->openTGZ($this->modelfile);
        $dir = opendir($targetPath);
        $content = null;
        
        while ($file = readdir($dir)) {
            if ($this->showFileExtension($file) == 'xml') {
                $filepath = $targetPath."/".$file;
                $content = file_get_contents($filepath);
                break;
            }
        }
        
        $this->closeTGZ($targetPath);
        
        return $content;
    }
    
    public function getTexturesNames() {
        $targetPath = $this->openTGZ($this->modelfile);
        $dir = opendir($targetPath);
        
        $names = array();
        
        while ($filename = readdir($dir)) {
            $extension = $this->showFileExtension($filename);
            if ($extension == 'png' || $extension == 'rgb') {
                $names[] = $filename;
            }
        }
        
        $this->closeTGZ($targetPath);
        
        return $names;
    }
    
    public function getTexture($filename) {
        $targetPath = $this->openTGZ($this->modelfile);
        $dir = opendir($targetPath);

        while ($file = readdir($dir)) {
            if ($file == $filename) {
                $filepath = $targetPath."/".$file;
                $content = file_get_contents($filepath);
                break;
            }
        }
        
        $this->closeTGZ($targetPath);
        
        return $content;
    }

}

?>