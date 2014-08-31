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

-- 1.) Load GeoPackage into one single GRASS map.
-- 2.) Check and clean in GRASS.
-- 3.) Create one single polygon by dissolving all layers into one
-- 4.) Export from GRASS into PG via "v.out.postgis" into one single PG
--     layer and call this function.
-- 5.) Build a single geometry from new landcover for cutting ("ST_Collect"
--     builds a MultiPolygon, "ST_Union" builds a Polygon)
-- 6.) Select the affected "cs_*" layers.
-- 7.) Identify affected polygons: Quick pre-selection via bounding box test,
--     intersection test via "ST_Intersects" for the candidates
-- 8.) Loop through affected polygons and check which polygons are fully
--     withing new CS area via "ST_Within", delete these
-- 9.) Intersect remaining affected polygons with cutout-layer, delete
--     these polygons, check the result of "ST_Difference" via
--     "ST_NumGeometries" to avoid multipolygons ("ST_Dump" or
--     generate_series ?) and store back to layer.
--

CREATE OR REPLACE FUNCTION fn_CSMerge(grasslayer varchar)
    RETURNS setof text
AS $BODY$
    DECLARE
        getcslayers varchar := $$SELECT f_table_name FROM geometry_columns WHERE f_table_name LIKE 'cs_%' AND type LIKE 'POLYGON' ORDER BY f_table_name;$$;
        bboxtest varchar;
        xstest varchar;
        intest varchar;
        delobj varchar;
        diffobj varchar;
        testmulti varchar;
        unrollmulti varchar;
        delmulti varchar;
        backdiff varchar;
        intersects bool;
        within bool;
        cslayer record;
        ogcfid record;
        multifid record;
    BEGIN
        DROP TABLE IF EXISTS cshole;
        CREATE TABLE cshole AS SELECT ST_Collect(wkb_geometry) AS wkb_geometry FROM base_collect;
        ALTER TABLE cshole ADD COLUMN ogc_fid serial NOT NULL;
        ALTER TABLE cshole ADD CONSTRAINT "enforce_dims_wkb_geometry" CHECK (ST_NDims(wkb_geometry) = 2);
        ALTER TABLE cshole ADD CONSTRAINT "enforce_geotype_wkb_geometry" CHECK (GeometryType(wkb_geometry) = 'MULTIPOLYGON'::text);
        ALTER TABLE cshole ADD CONSTRAINT "enforce_srid_wkb_geometry" CHECK (ST_SRID(wkb_geometry) = 4326);

        FOR cslayer IN
            EXECUTE getcslayers
        LOOP  -- through layers
            bboxtest := concat('SELECT ogc_fid FROM ', quote_ident(cslayer.f_table_name), ' WHERE wkb_geometry && (SELECT wkb_geometry FROM cshole) ORDER BY ogc_fid;');
            FOR ogcfid IN
                EXECUTE bboxtest
            LOOP  -- through candidate objects
                xstest := concat('SELECT ST_Intersects((SELECT wkb_geometry FROM cshole), (SELECT wkb_geometry FROM ', quote_ident(cslayer.f_table_name), ' WHERE ogc_fid = ', ogcfid.ogc_fid, '));');
                EXECUTE xstest INTO intersects;
                CASE WHEN intersects IS TRUE THEN
                    intest := concat('SELECT ST_Within((SELECT wkb_geometry FROM ', quote_ident(cslayer.f_table_name), ' WHERE ogc_fid = ', ogcfid.ogc_fid, '), (SELECT wkb_geometry FROM cshole));');
                    EXECUTE intest INTO within;
                    CASE WHEN within IS FALSE THEN
                        DROP TABLE IF EXISTS csdiff;
                        diffobj := concat('CREATE TABLE csdiff AS SELECT ST_Difference((SELECT wkb_geometry FROM ', quote_ident(cslayer.f_table_name), ' WHERE ogc_fid = ', ogcfid.ogc_fid, '), (SELECT wkb_geometry FROM cshole)) AS wkb_geometry;');
                        RAISE NOTICE '%', diffobj;
                        EXECUTE diffobj;
                        ALTER TABLE csdiff ADD COLUMN ogc_fid serial NOT NULL;
                        testmulti := 'SELECT ogc_fid FROM csdiff WHERE ST_NumGeometries(wkb_geometry) IS NOT NULL;';
                        FOR multifid IN
                            EXECUTE testmulti
                        LOOP
                            unrollmulti := concat('INSERT INTO csdiff (wkb_geometry) (SELECT ST_GeometryN(wkb_geometry, generate_series(1, ST_NumGeometries(wkb_geometry))) AS wkb_geometry FROM csdiff WHERE ogc_fid = ', multifid.ogc_fid, ');');
                            delmulti := concat('DELETE FROM csdiff WHERE ogc_fid = ', multifid.ogc_fid, ';');
                            EXECUTE unrollmulti;
                            EXECUTE delmulti;
                        END LOOP;
                        backdiff := concat('INSERT INTO ', quote_ident(cslayer.f_table_name), ' (wkb_geometry) (SELECT wkb_geometry FROM csdiff);');
--                        backdiff := 'INSERT INTO difftemp (wkb_geometry) (SELECT wkb_geometry FROM csdiff);';
                        RAISE NOTICE '%', backdiff;
                        EXECUTE backdiff;
                    ELSE NULL;
                    END CASE;
                    delobj := concat('DELETE FROM ', quote_ident(cslayer.f_table_name), ' WHERE ogc_fid = ', ogcfid.ogc_fid, ';');
                    RAISE NOTICE '%', delobj;
                    EXECUTE delobj;
                ELSE NULL;
                END CASE;
            END LOOP;
        END LOOP;
    END;
$BODY$
LANGUAGE plpgsql;
