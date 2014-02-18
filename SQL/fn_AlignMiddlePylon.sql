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
-- Name: fn_AlignMiddlePylon
-- Coder: FredR
-- Date: 2014/01/10
-- Purpose: Align any pylon of a serie, except first or last.
--          Parameters are p1=geometry of pylon[x-1]  p2=geometry of pylon[x]  p3=geometry of pylon[x+1]
--------------------------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_AlignMiddlePylon(IN p1 geometry,IN p2 geometry,IN p3 geometry) RETURNS float AS $PROC$
DECLARE
BEGIN
    RETURN (degrees(ST_Azimuth(p1,p2))+degrees(ST_Azimuth(p2,p3)))/2;
END;
$PROC$ LANGUAGE plpgsql;
