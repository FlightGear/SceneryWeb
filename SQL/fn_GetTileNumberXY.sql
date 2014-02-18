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
-- Name: fn_GetTileNumberXY
-- Coder: FredR
-- Date: 2013/12/20
-- Purpose: Returns the FlightGear tile number of a given point lon and lat.
--          Makes use of subfunction fn_GetTileNumber.
--------------------------------------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_GetTileNumberXY(float,float) CASCADE;

CREATE FUNCTION fn_GetTileNumberXY(IN lon float,IN lat float) RETURNS integer AS $PROC$
DECLARE
    x text;
    n integer;
BEGIN
    x := 'SRID=4326;POINT('||lon::text||' '||lat::text||')';
    n := fn_GetTileNumber(ST_GeomFromEWKT(x));
    RETURN n;
END;
$PROC$ LANGUAGE plpgsql;
