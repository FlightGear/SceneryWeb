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
-- Name: fn_GetDistanceInMeters
-- Coder: FredR
-- Date: 2014/01/20
-- Purpose: Returns the geographic distance between two points, in meters.
--------------------------------------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_GetDistanceInMeters(geometry,geometry) CASCADE;

CREATE FUNCTION fn_GetDistanceInMeters(IN lg1 geometry, IN lg2 geometry) RETURNS float AS $PROC$
    SELECT ST_Distance(lg1::geography,lg2::geography);
$PROC$ LANGUAGE sql;
