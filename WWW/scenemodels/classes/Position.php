<?php

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
 * Position
 *
 * @author Julien Nguyen <julien.nguyen3@gmail.com>
 */
class Position {
    private $longitude;
    private $latitude;
    
    /**
     * Gets longitude
     * @return float longitude
     */
    public function getLongitude() {
        return $this->longitude;
    }
    
    /**
     * Sets longitude
     * @param float $longitude
     */
    public function setLongitude($longitude) {
        $this->longitude = $longitude;
    }
    
    /**
     * Gets latitude
     * @return float latitude
     */
    public function getLatitude() {
        return $this->latitude;
    }
    
    /**
     * Sets latitude
     * @param float $latitude
     */
    public function setLatitude($latitude) {
        $this->latitude = $latitude;
    }
}
