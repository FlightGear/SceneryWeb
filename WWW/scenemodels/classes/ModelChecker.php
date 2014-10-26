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
    private $depth;
    static private $validDimension = array(1, 2, 4, 8, 16, 32, 64, 128, 256, 512, 1024, 2048, 4096, 8192);
    
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
    
    public function checkFiles($targetPath, $xmlPath, $ac3dName, $pngNames) {
        $exceptions = array();

        // Check XML if set
        if (!is_null($xmlPath) && file_exists($xmlPath)) {
            $exceptions = array_merge($exceptions, $this->checkXML($xmlPath, $ac3dName));
        }

        // Check AC3D file
        $ac3dPath = $targetPath.$ac3dName;
        $exceptions = array_merge($exceptions, $this->checkAC3D($ac3dPath, $pngNames));

        // Check textures files
        for ($i=0; $i<12; $i++) {
            if (isset($pngNames[$i]) && ($pngNames[$i] != '')) {
                $pngPath  = $targetPath.$pngNames[$i];
                $pngName  = $pngNames[$i];

                $exceptions = array_merge($exceptions, $this->checkPNG($pngName, $pngPath));
            }
        }

        // Check thumbnail file
        $exceptions = array_merge($exceptions, $this->checkThumbnail($thumbPath));
        
        return $exceptions;
    }
    
    public function checkXML($xmlPath, $ac3dName) {
        $this->depth = array();
        $errors = array();
        $xml_parser = xml_parser_create();

        xml_set_object($xml_parser, $this);
        xml_set_element_handler($xml_parser, "startElement", "endElement");

        $fp = fopen($xmlPath, "r");
        if (!$fp) {
            $errors[] = new Exception("Could not open XML.");
        } else {
            while ($data = fread($fp, 4096)) {
                // check if tags are closed and if <PropertyList> is present
                if (!xml_parse($xml_parser, $data, feof($fp))) {
                    $errors[] = new Exception("XML error : ".xml_error_string(xml_get_error_code($xml_parser))." at line ".xml_get_current_line_number($xml_parser));
                }
            }
            xml_parser_free($xml_parser);
        }

        if (count($errors) == 0) {
            // Check if <path> == $ac3dName
            $xmlcontent = simplexml_load_file($xmlPath);
            if ($ac3dName != $xmlcontent->path) {
                $errors[] = new Exception("The value of the &lt;path&gt; tag in your XML file doesn't match the AC file you provided!");
            }

            // Check if the file begin with <?xml> tag
            $xmltag = str_replace(array("<", ">"), array("&lt;", "&gt;"), file_get_contents($xmlPath));
            if (!preg_match('#^&lt;\?xml version="1\.0" encoding="UTF-8" \?&gt;#i', $xmltag)) {
                $errors[] = new Exception("Your XML must start with &lt;?xml version=\"1.0\" encoding=\"UTF-8\" ?&gt;!");
            }
        }
        
        return $errors;
    }
    
    private function startElement($parser, $name, $attrs) {
        $parserInt = intval($parser);
        if (!isset($this->depth[$parserInt])) {
            $this->depth[$parserInt] = 0;
        }
        $this->depth[$parserInt]++;
    }

    private function endElement($parser, $name) {
        $parserInt = intval($parser);
        if (!isset($this->depth[$parserInt])) {
            $this->depth[$parserInt] = 0;
        }
        $this->depth[$parserInt]--;
    }
    
    public function checkAC3D($ac3dPath, $pngNames) {
        $errors = array();
        $handle = fopen($ac3dPath, 'r');

        if (!$handle) {
            $errors[] = new Exception("The AC file does not exist on the server. Please try to upload it again!");
            return $errors;
        }
        
        $i = 1;
        while (!feof($handle)) {
            $line = fgets($handle);
            $line = rtrim($line, "\r\n") . PHP_EOL;

            // Check if the file begins with the string "AC3D"
            if ($i == 1 && substr($line,0,4) != "AC3D") {
                $errors[] = new Exception("The AC file does not seem to be a valid AC3D file. The first line must show \"AC3Dx\" with x = version");
            }

            // Check if the texture reference matches $pngName
            if (preg_match('#^texture#', $line)) {
                $data = preg_replace('#texture "(.+)"$#', '$1', $line);
                $data = substr($data, 0, -1);
                if (!in_array($data, $pngNames)) {
                    $errors[] = new Exception("The texture reference (".$data.") in your AC file at line ".$i." seems to have a different name than the PNG texture(s) file(s) name(s) you provided!");
                }
            }
            $i++;
        }
        fclose($handle);
        
        return $errors;
    }
    
    public function checkPNG($pngName, $pngPath) {
        $errors = array();
        
        if (file_exists($pngPath)) {
            $tmp    = getimagesize($pngPath);
            $width  = $tmp[0];
            $height = $tmp[1];
            $mime   = $tmp["mime"];

            // Check if PNG file is a valid PNG file (compare the type file)
            if ($mime != "image/png") {
                $errors[] = new Exception("Your texture file does not seem to be a PNG file. Please upload a valid PNG file.");
            }

            // Check if PNG dimensions are a multiple of ^2
            if (!in_array($height, $this->validDimension) || !in_array($width, $this->validDimension)) {
                $errors[] = new Exception("The size in pixels of your texture file (".$pngName.") appears not to be a power of 2.");
            }
        }
        else {
            $errors[] = new Exception("The texture file does not exist on the server. Please try to upload it again.");
        }
        
        return $errors;
    }
    
    
    public function checkThumbnail($thumbPath) {
        $errors = array();
        
        if (file_exists($thumbPath)) {
            $tmp    = getimagesize($thumbPath);
            $width  = $tmp[0];
            $height = $tmp[1];
            $mime   = $tmp["mime"];

            // Check if JPEG file is a valid JPEG file (compare the type file)
            if ($mime != "image/jpeg") {
                $errors[] = new Exception("Your thumbnail file does not seem to be a JPEG file. Please upload a valid JPEG file.");
            }

            // Check if PNG dimensions are a multiple of ^2
            if ($height != 240 || $width != 320) {
                $errors[] = new Exception("The dimension in pixels of your thumbnail file (".$width."x".$height.") does not seem to be 320x240.");
            }
        }
        else {
            $errors[] = new Exception("The thumbnail file does not exist on the server. Please try to upload it again.");
        }
        
        return $errors;
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
