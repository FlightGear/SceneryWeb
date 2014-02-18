-- Copyright (C) 2013 - 2014  FlightGear scenery team
--
-- This program is free software; you can redistribute it and/or
-- modify it under the terms of the GNU General Public License as
-- published by the Free Software Foundation; either version 2 of the
-- License, or (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful, but
-- WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
-- General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

--------------------------------------------------------------------------------------------
-- Name: fn_GetCountryCodeTwo
-- Coder: FredR
-- Date: 2013/12/20
-- Purpose: Returns the ISO 2chars country code of a given point.
-- Caution: The parsing is mainly based on gadm2 table, whose accuracy is pretty questionnable !
--          May return blank country code even in very well known location because of a badly designed coastline...
--------------------------------------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_GetCountryCodeTwo(geometry) CASCADE;

CREATE FUNCTION fn_GetCountryCodeTwo(IN lg geometry) RETURNS character AS $PROC$
    SELECT co_code FROM gadm2, fgs_countries WHERE ST_Within(lg, gadm2.wkb_geometry) AND gadm2.iso ILIKE fgs_countries.co_three;
$PROC$ LANGUAGE sql;
