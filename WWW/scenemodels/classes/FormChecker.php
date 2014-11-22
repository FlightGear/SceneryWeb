<?php

/*
 * Copyright (C) 2014 julien
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
 * Description of FormChecker
 *
 * @author julien
 */
class FormChecker {
    static public $regex = array(
        'comment' => "/^[0-9a-z-A-Z\';:!?@\-_\. ]+$/u",
        'stg' => '/^[a-zA-Z0-9\_\.\-\,\/]+$/u',
        'model_filepath' => '/^[a-z0-9_\/.-]$/i',
        'modelid' => '/^[0-9]+$/u',
        'modelgroupid' => '/^[0-9]+$/',
        'modelname' => '/^[0-9a-zA-Z;!?@\-_\.\(\)\[\]+ ]+$/',
        'filename' => '/^[a-zA-Z0-9_.-]*$/u',
        'png_filename' => '/^[a-zA-Z0-9_.-]+\.(png|PNG)$/u',
        'ac3d_filename' => '/^[a-zA-Z0-9_.-]+\.(ac|AC)$/u',
        'xml_filename' => '/^[a-zA-Z0-9_.-]+\.(xml|XML)$/u',
        'authorid' => '#^[0-9]{1,3}$#',
        'email' => '/^[0-9a-zA-Z_\-.]+@[0-9a-z_\-]+\.[0-9a-zA-Z_\-.]+$/u',
        'objectid' => '/^[0-9]+$/u',
        'countryid' => '#^[a-zA-Z]{1,3}$#',
        'long_lat' => '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',
        'gndelevation' => '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',
        'offset' => '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',
        'heading' => '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',
        'obtext' => '/^[0-9a-zA-Z_\-. \[\]()]+$/u',
        'sig' => '/[0-9a-z]/',
        'pageoffset' => '/^[0-9]+$/u'
       );

    // Checks if the id is a model group id
    // ================================================
    static public function isModelGroupId($idToCheck) {
        return preg_match(self::$regex['modelgroupid'], $idToCheck);
    }

    // Checks if the id is a model id
    // ================================================
    static public function isModelId($idToCheck) {
        return preg_match(self::$regex['modelid'], $idToCheck)
               && $idToCheck > 0;
    }

    // Checks if the name is a model name
    // ================================================
    static public function isModelName($name) {
        return preg_match(self::$regex['modelname'], $name);
    }

    // Checks if the id is an object id
    // ================================================
    static public function isObjectId($idToCheck) {
        return $idToCheck > 0
               && preg_match(self::$regex['objectid'], $idToCheck);
    }

    // Checks if the id is an author id
    // ================================================
    static public function isAuthorId($idToCheck) {
        return $idToCheck > 0
               && preg_match(self::$regex['authorid'], $idToCheck);
    }

    // Checks if the given variable is a latitude
    // ================================================
    static public function isLatitude($value) {
        return strlen($value) <= 20
               && $value <= 90
               && $value >= -90
               && preg_match(self::$regex['long_lat'], $value);
    }

    // Checks if the given variable is a longitude
    // ================================================
    static public function isLongitude($value) {
        return strlen($value) <= 20
               && $value <= 180
               && $value >= -180
               && preg_match(self::$regex['long_lat'], $value);
    }

    // Checks if the given variable is a country id
    // ================================================
    static public function isCountryId($value) {
        return $value != ""
               && preg_match(self::$regex['countryid'], $value);
    }

    // Checks if the given variable is a ground elevation
    // ================================================
    static public function isGndElevation($value) {
        return strlen($value) <= 20
               && preg_match(self::$regex['gndelevation'], $value);
    }

    // Checks if the given variable is an offset
    // ================================================
    static public function isOffset($value) {
        return strlen($value) <= 20
               && preg_match(self::$regex['offset'], $value)
               && $value < 1000
               && $value > -1000;
    }

    // Checks if the given variable is a heading
    // ================================================
    static public function isHeading($value) {
        return strlen($value) <= 20
               && preg_match(self::$regex['heading'], $value)
               && $value < 360
               && $value >= 0;
    }

    // Checks if the given variable is a comment
    // ================================================
    static public function isComment($value) {
        return strlen($value) <= 100
               && preg_match(self::$regex['comment'], $value);
    }

    // Checks if the given variable is an email
    // ================================================
    static public function isEmail($value) {
        return strlen($value) <= 50
               && preg_match(self::$regex['email'], $value);
    }

    // Checks if the given variable is an sig id
    // ================================================
    static public function isSig($value) {
        return strlen($value) == 64
               && preg_match(self::$regex['sig'], $value);
    }
   
    // Checks if the given variable is a AC3D filename
    // ================================================
    static public function isAC3DFilename($filename) {
        return preg_match(self::$regex['ac3d_filename'], $filename);
    }
   
    // Checks if the given variable is a PNG filename
    // ================================================
    static public function isPNGFilename($filename) {
        return preg_match(self::$regex['png_filename'], $filename);
    }
   
    // Checks if the given variable is a XML filename
    // ================================================
    static public function isXMLFilename($filename) {
        return preg_match(self::$regex['xml_filename'], $filename);
    }
   
    // Checks if the given variable is a filename
    // ================================================
    static public function isFilename($filename) {
        return preg_match(self::$regex['filename'], $filename);
    }
   
    // Checks if the given variable is an obtext
    // ================================================
    static public function isObtext($value) {
        return strlen($value) > 0
                && strlen($value) <= 100
                && preg_match(self::$regex['obtext'], $value);
    }
   
}
