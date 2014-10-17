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

-- Derive center and most frequent runway heading from runway table.

BEGIN TRANSACTION;
    DROP MATERIALIZED VIEW v_apt_heading;
    CREATE MATERIALIZED VIEW v_apt_heading AS (
        WITH h AS (
            SELECT icao,
                (CASE WHEN COALESCE(substring(rwy_num1 FROM '[0-9]*'), rwy_num1) != ''
                    THEN COALESCE(substring(rwy_num1 FROM '[0-9]*'), rwy_num1)
                    ELSE rwy_num1
                    END)::numeric * 10 AS heading,
                COUNT(*),
                ROW_NUMBER() OVER (PARTITION BY icao ORDER BY icao, COUNT(*) DESC) AS rnk
            FROM apt_runway
            WHERE left(rwy_num1, 1) != 'H'
            GROUP BY icao, heading)

        SELECT ROW_NUMBER() OVER (ORDER BY a.icao) AS ogc_fid,
            a.icao AS icao, ST_Centroid(ST_Collect(a.wkb_geometry)) AS wkb_geometry, h.heading
        FROM apt_runway AS a, h
        WHERE a.icao = h.icao AND h.rnk = 1
        GROUP BY a.icao, h.heading
        ORDER BY icao);
    GRANT SELECT ON v_apt_heading TO webuser;
COMMIT;
