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
-- Name: fn_GetModelPath
-- Coder: FredR
-- Date: 2014/01/15
-- Purpose: Returns the model path string of a given model id number.
-- Exemple: Model number 520 returns the string: "Models/Power/generic_pylon_50m.ac"
--------------------------------------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_GetModelPath(integer) CASCADE;                                                                                                                                                                      

CREATE FUNCTION fn_GetModelPath(IN model integer) RETURNS character AS $PROC$
DECLARE
    r RECORD;
BEGIN
    SELECT INTO r mg_path,mo_path FROM fgs_models  LEFT OUTER JOIN fgs_modelgroups  ON mo_shared=mg_id WHERE mo_id=model;
    IF NOT FOUND THEN
       RETURN '';
    ELSE
       RETURN 'Models/'||r.mg_path||r.mo_path;
    END IF;
END;
$PROC$ LANGUAGE plpgsql;
