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

CREATE OR REPLACE FUNCTION fn_CSMerge(varchar)
    RETURNS void
AS $BODY$
    DECLARE
        getcslayers varchar;
        bboxtest varchar;
        xstest varchar;
        intest varchar;
        delobj varchar;
        diffobj varchar;
        testmulti varchar;
        unrollmulti varchar;
        delmulti varchar;
        backdiff varchar;
        dropdiff varchar;
        cslayer record;
        ogcfid record;
        multifid record;
    BEGIN
       getcslayers := 'SELECT f_table_name FROM geometry_columns WHERE f_table_name LIKE $$cs_%$$ AND type LIKE $$POLYGON$$ ORDER BY f_table_name';
        FOR cslayer IN
            EXECUTE getcslayers
        LOOP  -- through layers
            bboxtest := 'SELECT ogc_fid FROM quote_ident(cslayer.f_table_name) WHERE wkb_geometry && (SELECT wkb_geometry FROM cshole) ORDER BY ogc_fid';
            FOR ogcfid IN
                EXECUTE bboxtest
            LOOP  -- through candidate objects
                xstest := 'SELECT ST_Intersects((SELECT wkb_geometry FROM cshole), (SELECT wkb_geometry FROM quote_ident(cslayer.f_table_name) WHERE ogc_fid = ogcfid.ogc_fid))';
                CASE WHEN EXECUTE xstest THEN
                    intest := 'SELECT ST_Within((SELECT wkb_geometry FROM quote_ident(cslayer.f_table_name) WHERE ogc_fid = ogcfid.ogc_fid), (SELECT wkb_geometry FROM cshole))';
                    delobj := 'DELETE FROM quote_ident(cslayer.f_table_name) WHERE ogc_fid = ogcfid.ogc_fid';
                    CASE WHEN EXECUTE intest THEN
                        RAISE NOTICE '%', delobj;
                    ELSE
                        diffobj := 'CREATE TABLE csdiff AS SELECT ST_Difference((SELECT wkb_geometry FROM quote_ident(cslayer.f_table_name) WHERE ogc_fid = ogcfid.ogc_fid), (SELECT wkb_geometry FROM cshole))';
                        RAISE NOTICE '%', diffobj;
                        EXECUTE diffobj;
                        testmulti := 'SELECT ogc_fid FROM csdiff WHERE ST_NumGeometries(wkb_geometry) IS NOT NULL';
                        FOR multifid IN
                            EXECUTE testmulti
                        LOOP
                            unrollmulti := 'INSERT INTO csdiff (wkb_geometry) (SELECT ST_GeometryN(wkb_geometry, generate_series(1, ST_NumGeometries(wkb_geometry))) AS wkb_geometry FROM csdiff WHERE ogc_fid = multifid.ogc_fid)';
                            delmulti := 'DELETE FROM csdiff WHERE ogc_fid = multifid.ogc_fid';
                            RAISE NOTICE '%', unrollmulti;
                            EXECUTE unrollmulti;
                            RAISE NOTICE '%', delmulti;
                        END LOOP;
                        RAISE NOTICE '%', delobj;
                        backdiff := 'INSERT INTO quote_ident(cslayer.f_table_name) (wkb_geometry) (SELECT wkb_geometry FROM csdiff)';
                        RAISE NOTICE '%', backdiff;
                        dropdiff := 'DROP TABLE csdiff';
                    END CASE;
                END CASE;
            END LOOP;
        END LOOP;
    END;
$BODY$
LANGUAGE 'plpgsql';
