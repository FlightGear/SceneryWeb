#!/usr/bin/env python
# -*- coding: utf-8 -*-
#
# Copyright (C) 2013 Martin Spott - Martin (at) flightgear (dot) org
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License as
# published by the Free Software Foundation; either version 2 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#

import os, sys
import psycopg2
from lxml import etree

db_params = {"host":"localhost", "database":"landcover", "user":"webuser"}
try:
    db_conn = psycopg2.connect(**db_params)
except:
    print "Cannot connect to database."
db_cur = db_conn.cursor()

def fn_pgexec(sql):
    try:
        db_cur.execute(sql)
    except:
        print "Cannot execute SQL statement."
    return db_cur

def fn_tower_twr(icao, dir):
    sql = "SELECT ST_X(wkb_geometry), ST_Y(wkb_geometry), hgt_tower_m FROM apt_twr WHERE icao LIKE '%s'" % icao

    fn_pgexec(sql)
    if db_cur.rowcount > 0:
        with open(dir + icao + ".twr.xml", "w") as file:
            db_result = db_cur.fetchall()
            PropertyList = etree.Element("PropertyList")
            tower = etree.SubElement(PropertyList, "tower")
            for row in db_result:
                twr = etree.SubElement(tower, "twr")
                lon = etree.SubElement(twr, "lon")
                lon.text = str(row[0])
                lat = etree.SubElement(twr, "lat")
                lat.text = str(row[1])
                elev = etree.SubElement(twr, "elev-m")
                elev.text = str(row[2])
            tree = etree.ElementTree(PropertyList)
            tree.write(file, pretty_print=True, xml_declaration=True, encoding="ISO-8859-1")
            file.close()

def fn_runway_threshold(icao, dir):
    sql = "SELECT DISTINCT ogc_fid, rwy_num1 \
        FROM apt_runway \
        WHERE icao LIKE '%s' \
        AND (rwy_num1 IS NOT NULL OR rwy_num2 IS NOT NULL) \
        ORDER BY rwy_num1" % icao
    fn_pgexec(sql)
    if db_cur.rowcount > 0:
        with open(dir + icao + ".threshold.xml", "w") as file:
            db_result = db_cur.fetchall()
            PropertyList = etree.Element("PropertyList")
            for id in db_result:
                sql = "SELECT ST_X(t.wkb_geometry), ST_Y(t.wkb_geometry), t.rwy_num, t.true_heading_deg, t.displaced_threshold_m, t.stopway_length_m \
                    FROM apt_threshold AS t, apt_runway AS r \
                    WHERE t.icao LIKE '%s' \
                    AND is_displaced IS FALSE \
                    AND r.ogc_fid = %d \
                    AND (t.rwy_num LIKE r.rwy_num1 OR t.rwy_num LIKE r.rwy_num2) \
                    ORDER BY t.rwy_num" % (icao, id[0])
                fn_pgexec(sql)
                if db_cur.rowcount > 0:
                    db_result = db_cur.fetchall()
                    runway = etree.SubElement(PropertyList, "runway")
                    for row in db_result:
                        threshold = etree.SubElement(runway, "threshold")
                        lon = etree.SubElement(threshold, "lon")
                        lon.text = str(row[0])
                        lat = etree.SubElement(threshold, "lat")
                        lat.text = str(row[1])
                        elev = etree.SubElement(threshold, "rwy")
                        elev.text = str(row[2])
                        hdg = etree.SubElement(threshold, "hdg-deg")
                        hdg.text = str(row[3])
                        displ = etree.SubElement(threshold, "displ-m")
                        displ.text = str(row[4])
                        stopw = etree.SubElement(threshold, "stopw-m")
                        stopw.text = str(row[5])
            tree = etree.ElementTree(PropertyList)
            tree.write(file, pretty_print=True, xml_declaration=True, encoding="ISO-8859-1")
            file.close()

def fn_runway_ils(icao, dir):
    sql = "SELECT DISTINCT r.ogc_fid, r.rwy_num1 \
        FROM apt_runway AS r, apt_ils AS i \
        WHERE r.icao LIKE '%s' \
        AND (r.rwy_num1 IS NOT NULL OR r.rwy_num2 IS NOT NULL) \
        AND r.icao LIKE i.icao \
        GROUP BY r.ogc_fid \
        HAVING COUNT(i.icao) > 0 \
        ORDER BY r.rwy_num1" % icao
    fn_pgexec(sql)
    if db_cur.rowcount > 0:
        with open(dir + icao + ".ils.xml", "w") as file:
            db_result = db_cur.fetchall()
            PropertyList = etree.Element("PropertyList")
            for id in db_result:
                sql = "SELECT ST_X(i.wkb_geometry), ST_Y(i.wkb_geometry), i.rwy_num, i.true_heading_deg, i.elevation_m, i.navaid_id \
                    FROM apt_ils AS i, apt_runway AS r \
                    WHERE i.icao LIKE '%s' \
                    AND r.ogc_fid = %d \
                    AND (i.rwy_num LIKE r.rwy_num1 OR i.rwy_num LIKE r.rwy_num2) \
                    ORDER BY i.rwy_num" % (icao, id[0])
                fn_pgexec(sql)
                if db_cur.rowcount > 0:
                    db_result = db_cur.fetchall()
                    runway = etree.SubElement(PropertyList, "runway")
                    for row in db_result:
                        ils = etree.SubElement(runway, "ils")
                        lon = etree.SubElement(ils, "lon")
                        lon.text = str(row[0])
                        lat = etree.SubElement(ils, "lat")
                        lat.text = str(row[1])
                        rwy = etree.SubElement(ils, "rwy")
                        rwy.text = str(row[2])
                        hdg = etree.SubElement(ils, "hdg-deg")
                        hdg.text = str(row[3])
                        elev = etree.SubElement(ils, "elev-m")
                        elev.text = str(row[4])
                        navid = etree.SubElement(ils, "nav-id")
                        navid.text = str(row[5])
            tree = etree.ElementTree(PropertyList)
            tree.write(file, pretty_print=True, xml_declaration=True, encoding="ISO-8859-1")
            file.close()

def fn_walk():
    sql = "SELECT DISTINCT icao FROM apt_airfield ORDER BY icao"

    fn_pgexec(sql)
    if db_cur.rowcount < 1:
        print "Database table empty !"
        return
    else:
        db_result = db_cur.fetchall()
        for row in db_result:
            airfield = row[0]
            directory = "Airports/%s/%s/%s/" % (airfield[0],airfield[1],airfield[2])
            if not os.path.exists(directory):
                os.makedirs(directory)
            fn_tower_twr(airfield, directory)
            fn_runway_threshold(airfield, directory)
            fn_runway_ils(airfield, directory)

fn_walk()

db_cur.close
db_conn.close

# EOF
