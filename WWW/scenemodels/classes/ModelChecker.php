<?php

require_once 'FormChecker.php';

/*
 * Copyright (C) 2014 Flightgear Team
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
 * Description of ModelChecker
 *
 * @author Julien Nguyen
 */
class ModelChecker {

    public function checkFilesNames($ac3dName, $xmlName, $thumbName, array $pngNames) {
        $exceptions = array();
        
        if (!FormChecker::isAC3DFilename($ac3dName)
                || ($xmlName != "" && !FormChecker::isXMLFilename($xmlName))) {
            $exceptions[] = new Exception("AC3D and XML name must used the following characters: 'a' to 'z', 'A' to 'Z', '0' to '9', '_', '.' or '_'");
        }

        // Checks PNG Filenames
        for ($i=0; $i<count($pngNames); $i++) {
            if (isset($pngNames[$i]) && $pngNames[$i] != "" && !FormChecker::isPNGFilename($pngNames[$i])) {
                $exceptions[] = new Exception("Textures' name must be *.png or *.PNG with the following characters: 'a' to 'z', 'A' to 'Z', '0' to '9', '_', '.' or '_'");
            }
        }

        if (count($exceptions) == 0 && 
                (remove_file_extension($thumbName) != remove_file_extension($ac3dName)."_thumbnail"
                || ($xmlName != "" && remove_file_extension($ac3dName) != remove_file_extension($xmlName)))) {
            $exceptions[] = new Exception("XML, AC and thumbnail file <u>must</u> share the same name. (i.e: tower.xml (if exists: currently ".$_FILES["xml_file"]['name']."), tower.ac (currently ".$ac3dName."), tower_thumbnail.jpeg (currently ".$thumbName.").");
            if (substr(remove_file_extension($thumbName), -10) != "_thumbnail") {
                $exceptions[] = new Exception("The thumbnail file name must end with *_thumbnail.");
            }
        }
        return $exceptions;
    }
    
    // Returns the extension of a file sent in parameter
    // =================================================

    public function showFileExtension($filepath) {
        preg_match('/[^?]*/', $filepath, $matches);
        $string = $matches[0];
        $pattern = preg_split('/\./', $string, -1, PREG_SPLIT_OFFSET_CAPTURE);

        if (count($pattern) > 1) {
            $filenamepart = $pattern[count($pattern)-1][0];
            preg_match('/[^?]*/', $filenamepart, $matches);
            return $matches[0];
        }
    }
    
    /**
     * Opens a working directory for the new uploaded model.
     * 
     * @param type $parentDir path
     * @return string new directory path
     * @throws Exception
     */
    public function openWorkingDirectory($parentDir) {
        $targetPath = $parentDir . "/static_".random_suffix()."/";
        while (file_exists($targetPath)) {
            usleep(500);    // Makes concurrent access impossible: the script has to wait if this directory already exists.
        }

        if (!mkdir($targetPath)) {
            throw new Exception("Impossible to create temporary directory ".$targetPath);
        }
        
        return $targetPath;
    }
    
    public function generateModelFilesPackage($targetDir, $modelFolderPath) {
        $phar = new PharData($targetDir . '/static.tar');                // Create archive file
        $phar->buildFromDirectory($modelFolderPath);                        // Fills archive file
        $phar->compress(Phar::GZ);                                     // Convert archive file to compress file
        unlink($targetDir . '/static.tar');                              // Delete archive file
        rename($targetDir . '/static.tar.gz', $targetDir.'/static.tgz');   // Rename compress file

        $handle    = fopen($targetDir."/static.tgz", "r");
        $contents  = fread($handle, filesize($targetDir."/static.tgz"));
        fclose($handle);
        
        return base64_encode($contents);                    // Dump & encode the file
    }
}
