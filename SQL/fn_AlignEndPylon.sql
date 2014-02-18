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
-- Name: fn_AlignEndPylon
-- Coder: FredR
-- Date: 2014/01/10
-- Purpose: Align first or last pylon of a serie.
--          For the first pylon, parameters are p1=geometry of pylon[1]  p2=geometry of pylon[2]
--          For the last pylon, parameters are p1=geometry of pylon[n-1]  p2=geometry of pylon[n]
--------------------------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_AlignEndPylon(IN p1 geometry,IN p2 geometry) RETURNS float AS $PROC$
DECLARE
BEGIN
    RETURN degrees(ST_Azimuth(p1,p2));
END;
$PROC$ LANGUAGE plpgsql;
