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

-- Helpers to provide path names and elevation/heading figures for
-- FlightGear scenery .stg-files

CREATE OR REPLACE FUNCTION fn_SceneDir(geometry)
    RETURNS varchar
AS $$
    DECLARE
        min_lon integer;
        min_lat integer;
        lon_char char(1);
        lat_char char(1);
    BEGIN
        min_lon := Abs(floor(floor(ST_X($1)) / 10) * 10);
        min_lat := Abs(floor(floor(ST_Y($1)) / 10) * 10);
        lon_char := (CASE WHEN (ST_X($1)) < 0 THEN 'w' ELSE 'e' END);
        lat_char := (CASE WHEN (ST_Y($1)) < 0 THEN 's' ELSE 'n' END);
        return concat(lon_char, lpad(CAST(min_lon AS varchar), 3, '0'), lat_char, lpad(CAST(min_lat AS varchar), 2, '0'));
    END
$$
LANGUAGE 'plpgsql';

--

CREATE OR REPLACE FUNCTION fn_SceneSubdir(geometry)
    RETURNS varchar
AS $$
    DECLARE
        min_lon integer;
        min_lat integer;
        lon_char char(1);
        lat_char char(1);
    BEGIN
        min_lon := Abs(floor(ST_X($1)));
        min_lat := Abs(floor(ST_Y($1)));
        lon_char := (CASE WHEN (ST_X($1)) < 0 THEN 'w' ELSE 'e' END);
        lat_char := (CASE WHEN (ST_Y($1)) < 0 THEN 's' ELSE 'n' END);
        return concat(lon_char, lpad(CAST(min_lon AS varchar), 3, '0'), lat_char, lpad(CAST(min_lat AS varchar), 2, '0'));
    END
$$
LANGUAGE 'plpgsql';

--

CREATE OR REPLACE FUNCTION fn_BoundingBox(geometry)
    RETURNS varchar
AS $$
    DECLARE
        min_lon integer;
        min_lat integer;
        max_lon integer;
        max_lat integer;
    BEGIN
        min_lon := floor(floor(ST_X($1)) / 10) * 10;
        min_lat := floor(floor(ST_Y($1)) / 10) * 10;
--        max_lon := ceil(ceil(ST_X($1)) / 10) * 10;
--        max_lat := ceil(ceil(ST_Y($1)) / 10) * 10;
        max_lon := min_lon + 10;
        max_lat := min_lat + 10;
        return concat('ST_SetSRID(''BOX3D(', min_lon, ' ',  min_lat, ', ', max_lon, ' ', max_lat, ')''::BOX3D, 4326)');
    END
$$
LANGUAGE 'plpgsql';

--

CREATE OR REPLACE FUNCTION fn_StgElevation(numeric, numeric)
    RETURNS numeric
AS $$
    DECLARE
        stgelevation numeric(7,2);
    BEGIN
        stgelevation := CASE WHEN $2 IS NOT NULL THEN ($1 + $2) ELSE $1 END;
        return stgelevation;
    END
$$
LANGUAGE 'plpgsql';

--

CREATE OR REPLACE FUNCTION fn_StgHeading(numeric)
    RETURNS numeric
AS $$
    DECLARE
        stgheading numeric(5,2);
    BEGIN
        stgheading := CASE WHEN $1 > 180 THEN (540 - $1) ELSE (180 - $1) END;
        return stgheading;
    END
$$
LANGUAGE 'plpgsql';
