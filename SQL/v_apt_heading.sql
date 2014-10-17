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
    -- This resembles a temporary table.
    CREATE MATERIALIZED VIEW v_apt_heading AS (
        WITH h AS (
            SELECT icao,
                -- Strip trailing non-numeric characters from runway designator.
                (CASE WHEN COALESCE(substring(rwy_num1 FROM '[0-9]*'), rwy_num1) != ''
                    THEN COALESCE(substring(rwy_num1 FROM '[0-9]*'), rwy_num1)
                    ELSE rwy_num1
                    -- Convert rnuway designation string into number as degrees.
                    END)::numeric * 10 AS heading,
                -- obsolete ....
                COUNT(*),
                -- Rank rnuway headings by number of occurrences.
                ROW_NUMBER() OVER (PARTITION BY icao ORDER BY icao, COUNT(*) DESC) AS rank
            FROM apt_runway
            -- FIXME, skip heliports, they start with a non-numeric character.
            WHERE left(rwy_num1, 1) != 'H'
            GROUP BY icao, heading)

        -- Views don't have serials, create a work-alike.
        SELECT ROW_NUMBER() OVER (ORDER BY a.icao) AS ogc_fid,
            -- Join weighted airport center with heading ....
            a.icao AS icao, ST_Centroid(ST_Collect(a.wkb_geometry)) AS wkb_geometry, h.heading
        FROM apt_runway AS a, h
        -- .... which has the highest rank.
        WHERE a.icao = h.icao AND h.rank = 1
        GROUP BY a.icao, h.heading
        ORDER BY icao);
    -- Let everybody read the view.
    GRANT SELECT ON v_apt_heading TO webuser;
COMMIT;
