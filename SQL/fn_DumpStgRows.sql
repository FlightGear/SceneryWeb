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

-- NOTE: Make sure the locale settings in general and the
-- number formatting in particular are set to "en_US.UTF-8" !

CREATE OR REPLACE FUNCTION fn_DumpStgRows(integer)
    RETURNS setof text
AS $$
    DECLARE
        tileno integer = $1;
    BEGIN
        RETURN QUERY
        WITH modelitems AS (SELECT mo_id AS id,
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
            AND mo_shared = mg_id),

        signitems AS (SELECT si_definition AS name,
            trim(trailing '.' FROM to_char(ST_X(wkb_geometry), 'FM990D999999999')) AS lon,
            trim(trailing '.' FROM to_char(ST_Y(wkb_geometry), 'FM990D999999999')) AS lat,
            trim(trailing '.' FROM to_char(si_gndelev::float, 'FM99990D999999999')) AS stgelev,
            trim(trailing '.' FROM to_char(fn_StgHeading(si_heading)::float, 'FM990D999999999')) AS stgheading
        FROM fgs_signs
        WHERE si_tile = tileno
            AND si_valid IS TRUE AND si_tile IS NOT NULL
            AND si_gndelev > -9999),

        modelrow AS (SELECT concat((CASE WHEN shared > 0 THEN concat('OBJECT_SHARED Models/', path) ELSE 'OBJECT_STATIC '  END),
            name, ' ', lon, ' ', lat, ' ', stgelev, ' ', stgheading)::text AS object
        FROM modelitems
        ORDER BY shared DESC, id, lon::float, lat::float,
            stgelev::float, stgheading::float),

        signrow AS (SELECT concat('OBJECT_SIGN ',
            name, ' ', lon, ' ', lat, ' ', stgelev, ' ', stgheading)::text AS object
        FROM signitems
        ORDER BY lon::float, lat::float,
            stgelev::float, stgheading::float),

        mo AS (SELECT string_agg(object, E'\n') AS mo FROM modelrow),
        si AS (SELECT string_agg(object, E'\n') AS si FROM signrow)

        SELECT (CASE
            WHEN COUNT(mo) = 1 AND COUNT(si) = 1 THEN concat(mo, E'\n', si)
            WHEN COUNT(mo) = 1 AND COUNT(si) = 0 THEN mo
            WHEN COUNT(mo) = 0 AND COUNT(si) = 1 THEN si
        END) AS ret
        FROM mo, si
        WHERE (SELECT COUNT(mo) FROM mo) > 0
            OR (SELECT COUNT(si) FROM si) > 0
        GROUP BY mo, si;

    END;
$$
LANGUAGE 'plpgsql';
