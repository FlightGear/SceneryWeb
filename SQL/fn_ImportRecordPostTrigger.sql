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
-- Name: fn_ImportRecordPostTrigger
-- Coder: FredR
-- Date: 2014/02/06
-- Purpose: Import table. Trigger that complete calculated fields after insert or update.
--------------------------------------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_ImportRecordPostTrigger() CASCADE;                                                                                                                                                                      

CREATE FUNCTION fn_ImportRecordPostTrigger() RETURNS TRIGGER AS $PROC$
BEGIN
    IF (TG_OP = 'UPDATE') OR (TG_OP = 'INSERT') THEN
       NEW.ob_country:=fn_GetCountryCodeTwo(NEW.wkb_geometry);
       NEW.ob_tile:=fn_GetTileNumber(NEW.wkb_geometry);
    END IF;
    RETURN NEW;
END;
$PROC$ LANGUAGE plpgsql;

CREATE TRIGGER Import_CalculateRecord BEFORE INSERT OR UPDATE ON fgs_import
    FOR EACH ROW EXECUTE PROCEDURE fn_ImportRecordPostTrigger();
