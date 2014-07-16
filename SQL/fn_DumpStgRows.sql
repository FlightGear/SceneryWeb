-- Copyright (C) 2012 - 2014  Martin Spott
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

-- Dump all .stg-rows for a given tile

CREATE OR REPLACE FUNCTION fn_DumpStgRows(integer)
    RETURNS setof varchar
AS $$
    DECLARE
        tileno integer = $1;
    BEGIN
        RETURN QUERY
        WITH items AS (SELECT mo_id AS id,
            (CASE WHEN mo_shared > 0 THEN 1 ELSE 0 END) AS shared,
            mg_path AS path,
            mo_path AS name,
            trim(trailing '.' FROM to_char(ST_X(wkb_geometry), 'FM990D999999999')) AS lon,
            trim(trailing '.' FROM to_char(ST_Y(wkb_geometry), 'FM990D999999999')) AS lat,
            trim(trailing '.' FROM to_char(fn_StgElevation(ob_gndelev, ob_elevoffset)::float, 'FM99990D999999999')) AS stgelev,
            trim(trailing '.' FROM to_char(fn_StgHeading(ob_heading)::float, 'FM990D999999999')) AS stgheading
        FROM fgs_objects, fgs_models, fgs_modelgroups
        WHERE ob_tile = tileno
            AND ob_valid IS TRUE AND ob_tile IS NOT NULL
            AND ob_model = mo_id AND ob_gndelev > -9999
            AND mo_shared = mg_id)
        SELECT concat((CASE WHEN shared > 0 THEN concat('OBJECT_SHARED Models/', path) ELSE 'OBJECT_STATIC '  END), name, ' ', lon, ' ', lat, ' ', stgelev, ' ', stgheading)::varchar
        FROM items
        ORDER BY shared DESC, id, lon::float, lat::float, stgelev::float, stgheading::float;

        RETURN QUERY
        WITH items AS (SELECT si_definition AS name, 
            trim(trailing '.' FROM to_char(ST_X(wkb_geometry), 'FM990D999999999')) AS lon,
            trim(trailing '.' FROM to_char(ST_Y(wkb_geometry), 'FM990D999999999')) AS lat,
            trim(trailing '.' FROM to_char(si_gndelev::float, 'FM99990D999999999')) AS stgelev,
            trim(trailing '.' FROM to_char(fn_StgHeading(si_heading)::float, 'FM990D999999999')) AS stgheading
        FROM fgs_signs
        WHERE si_tile = tileno
            AND si_valid IS TRUE AND si_tile IS NOT NULL
            AND si_gndelev > -9999)
        SELECT concat('OBJECT_SIGN ', name, ' ', lon, ' ', lat, ' ', stgelev, ' ', stgheading)::varchar
        FROM items
        ORDER BY lon::float, lat::float, stgelev::float, stgheading::float;
    END;
$$
LANGUAGE 'plpgsql';
