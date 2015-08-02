<?php
namespace submission;

/*
 * Copyright (C) 2015 FlightGear Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Filenames validator to check if filenames respect the naming convention
 *
 * @author Julien Nguyen <julien.nguyen3@gmail.com>
 * @copyright  2015 - FlightGear Team
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 */
class FilenamesValidator implements Validator {
    
    private $ac3dName;
    private $xmlName;
    private $pngNames;
    private $thumbName;
    
    /**
     * Constructor
     * 
     * @param type $ac3dName AC3D filename
     * @param type $xmlName XML filename
     * @param type $thumbName thumbnail filename
     * @param array $pngNames array of png filenames
     */
    public function __construct($ac3dName, $xmlName, $thumbName, array $pngNames) {
        $this->xmlName = $xmlName;
        $this->ac3dName = $ac3dName;
        $this->thumbName = $thumbName;
        $this->pngNames = $pngNames;
    }
    
    
    public function validate() {
        $exceptions = array();
        
        if (!\FormChecker::isAC3DFilename($this->ac3dName)
                || ($this->xmlName != "" && !\FormChecker::isXMLFilename($this->xmlName))) {
            $exceptions[] = new \Exception("AC3D and XML name must used the following characters: 'a' to 'z', 'A' to 'Z', '0' to '9', '_', '.' or '_'");
        }

        // Checks PNG Filenames
        for ($i=0; $i<count($this->pngNames); $i++) {
            if (isset($this->pngNames[$i]) && $this->pngNames[$i] != "" && !\FormChecker::isPNGFilename($this->pngNames[$i])) {
                $exceptions[] = new \Exception("Textures' name must be *.png or *.PNG with the following characters: 'a' to 'z', 'A' to 'Z', '0' to '9', '_', '.' or '_'");
            }
        }
        
        // Check thumbnail filename
        if (!\FormChecker::isThumbFilename($this->thumbName)) {
            $exceptions[] = new \Exception("Thumbnail name must be *.jpg or *.jpeg with the following characters: 'a' to 'z', 'A' to 'Z', '0' to '9', '_', '.' or '_'");
        }

        if (count($exceptions) == 0 && 
                ($this->nameWithoutExtension($this->thumbName) != $this->nameWithoutExtension($this->ac3dName)."_thumbnail"
                || ($this->xmlName != "" && $this->nameWithoutExtension($this->ac3dName) != $this->nameWithoutExtension($this->xmlName)))) {
            $exceptions[] = new \Exception("XML, AC and thumbnail file <u>must</u> share the same name. (i.e: tower.xml (if exists: currently ".$this->xmlName."), tower.ac (currently ".$this->ac3dName."), tower_thumbnail.jpeg (currently ".$this->thumbName.").");
            
            if (substr($this->nameWithoutExtension($this->thumbName), -10) != "_thumbnail") {
                $exceptions[] = new \Exception("The thumbnail file name must end with *_thumbnail.");
            }
        }
        
        return $exceptions;
    }
    
    private function nameWithoutExtension($path) {
        return pathinfo($path, PATHINFO_FILENAME);
    }
}
