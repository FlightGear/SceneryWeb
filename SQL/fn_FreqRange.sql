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

CREATE OR REPLACE FUNCTION fn_FreqRange(numeric, numeric, numeric)
    RETURNS SETOF json
AS $$
    DECLARE
        lon numeric := $1;
        lat numeric := $2;
        range numeric := $3;
    BEGIN
        RETURN QUERY
        WITH res AS (SELECT icao,
            CAST(
                ST_Distance_Spheroid(
                    ST_PointFromText(concat('POINT(6.5 51.5)'), 4326),
                    wkb_geometry,
                    'SPHEROID["WGS84",6378137.000,298.257223563]')
                AS numeric) AS dist
        FROM v_apt_heading)

        SELECT array_to_json(array_agg(row_to_json(t))) AS freq
        FROM (
            SELECT f.icao,
                f.freq_name,
                f.freq_mhz,
                round(res.dist / 1852.01, 1)
                AS dist
            FROM apt_freq AS f,
                res
            WHERE res.dist < range * 1852.01
            AND f.icao = res.icao
            ORDER BY res.dist, f.icao, f.freq_name)
        AS t;
    END;
$$
LANGUAGE 'plpgsql';

-- SELECT fn_FreqRange(6.5, 51.5, 30);
