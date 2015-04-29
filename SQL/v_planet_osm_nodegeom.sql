-- Copyright (C) 2015  Martin Spott
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

-- Derive geometry from bare OSM nodes.

CREATE OR REPLACE VIEW v_planet_osm_nodegeom AS (
    SELECT id,
        ST_PointFromText(
            concat('POINT(', lon/10000000::numeric, ' ', lat/10000000::numeric, ')'),
            4326) AS wkb_geometry
    FROM planet_osm_nodes);
