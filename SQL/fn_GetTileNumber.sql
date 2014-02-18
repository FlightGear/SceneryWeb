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
-- Name: fn_GetTileNumber
-- Coder: FredR
-- Date: 2013/12/20
-- Purpose: Returns the FlightGear tile number of a given point geometry, based on SimGear 
--          reference calculation.
--------------------------------------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_GetTileNumber(geometry) CASCADE;

CREATE FUNCTION fn_GetTileNumber(IN lg geometry) RETURNS integer AS $PROC$
DECLARE
    epsilon CONSTANT float := 0.0000001;
    dlon float;
    dlat float;
    lon integer;
    lat integer;
    difflon float;
    difflat float;
    bx integer;
    a integer;
    b integer;
    l float;
    r integer;
    w float;
    x integer;
    y integer;
BEGIN
    dlon := ST_X(lg);
    dlat := ST_Y(lg);

    IF abs(difflon) < epsilon THEN
       lon := trunc(dlon);
    ELSIF dlon >= 0 THEN
       lon := trunc(dlon);
    ELSE
       lon := floor(dlon);
    END IF;
       difflon := (dlon-lon);

    IF abs(difflat) < epsilon THEN
       lat := trunc(dlat);
    ELSIF dlat >= 0 THEN
       lat := trunc(dlat);
    ELSE
       lat := floor(dlat);
    END IF;
       difflat := (dlat-lat);

    IF    dlat >= 89.0 THEN
       w := 12.0;
    ELSIF dlat >= 86.0 THEN 
       w := 4.0;
    ELSIF dlat >= 83.0 THEN 
       w := 2.0;
    ELSIF dlat >= 76.0 THEN 
       w := 1.0;
    ELSIF dlat >= 62.0 THEN 
       w := 0.5;
    ELSIF dlat >= 22.0 THEN 
       w := 0.25;
    ELSIF dlat >= -22.0 THEN
       w := 0.125;
    ELSIF dlat >= -62.0 THEN 
       w := 0.25;
    ELSIF dlat >= -76.0 THEN 
       w := 0.5;
    ELSIF dlat >= -83.0 THEN 
       w := 1.0;
    ELSIF dlat >= -86.0 THEN 
       w := 2.0;
    ELSIF dlat >= -89.0 THEN 
       w := 4.0;
    ELSE
       w := 12.0;
    END IF;
	
    IF w <= 1.0 THEN 
       x := trunc(difflon/w);
    ELSE
       lon := floor(floor((lon + epsilon)/w)*w);
       IF lon < -180 THEN
          lon := -180;
       END IF;
       x := 0;
    END IF;
	
    y := trunc(difflat*8);
    y := y<<3;

    a := (lon+180)<<14;
    b := (lat+90)<<6;
    r := a+b+y+x;

    RETURN r;
END;
$PROC$ LANGUAGE plpgsql;
