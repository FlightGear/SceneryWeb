<?php

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
 * Description of ValidatorsSet
 *
 * @author Julien Nguyen
 */
class ValidatorsSet {
    private $validators;
    private $errors;
    
    function __construct() {
        $this->validators = array();
        $this->errors = array();
    }
    
    public function addValidator($validator) {
        $this->validators[] = $validator;
    }
    
    public function validate() {
        foreach ($this->validators as $validator) {
            $this->errors = array_merge($this->errors, $validator->validate());
        }
        
        return $this->errors;
    }
}
