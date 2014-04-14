-- Copyright (C) 2014  Martin Spott
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

-- Helpers to provide path names and elevation/heading figures for
-- FlightGear scenery .stg-files

CREATE OR REPLACE FUNCTION fn_DlTable(uuid)
    RETURNS setof text
AS $BODY$
    DECLARE
        tab record;
        item varchar;
        selectsql varchar;
        countsql varchar;
    BEGIN
        item := feature FROM download WHERE uuid = $1;
        selectsql := concat('SELECT * FROM geometry_columns WHERE f_table_name LIKE $$', item, '_%$$;');
        FOR tab IN
            EXECUTE selectsql
        LOOP
            countsql := concat('SELECT CASE WHEN COUNT(wkb_geometry)::integer > 0 THEN $$', quote_ident(tab.f_table_name), '$$ ELSE NULL END FROM ', quote_ident(tab.f_table_name), ' WHERE wkb_geometry && (SELECT wkb_geometry FROM download WHERE uuid = $$', $1, '$$);');
            RETURN QUERY EXECUTE countsql;
        END LOOP;
    RETURN;
    END;
$BODY$ LANGUAGE plpgsql;
