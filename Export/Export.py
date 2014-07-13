#!/usr/bin/env python
# -*- coding: utf-8 -*-
#
# Copyright (C) 2004 - 2014  Jon Stockill, Martin Spott
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

import os, sys, io
import subprocess

import psycopg2, psycopg2.extras
from subprocess import Popen, PIPE, STDOUT
import base64
import fnmatch
import tarfile

sys.stdout = os.fdopen(sys.stdout.fileno(), "w", 0)

homedir = os.path.expanduser("~")
fgscenery = os.path.expanduser("~fgscenery")
statusfile = open(os.path.join(homedir, ".exportstatus"), "w")
workdir = os.path.join(fgscenery, "Dump")
statusfile.write("running\n")
statusfile.flush()

pghost = "localhost"
pgdatabase = "landcover"
pguser = "jstockill"
db_params = {"host":pghost, "database":pgdatabase, "user":pguser}

# Save this for later use by subprocesses like:
#    mySubproc = os.path.join(basedir, "mySubproc")
#    subprocess.check_call(mySubproc, env=pgenv, shell=True)
basedir = os.path.dirname(os.path.realpath(__file__))
pgenv = dict(os.environ)
pgenv["PGHOST"] = pghost
pgenv["PGDATABASE"] = pgdatabase
pgenv["PGUSER"] = pguser

gl_debug = True  # FIXME

gl_sqlPosition = ""
gl_sqlMeta = ""
gl_sqlWhere = ""
gl_sqlOrder = ""

try:
    os.chdir(workdir)
except:
    sys.exit("Cannot change into work dir.")

try:
    db_conn = psycopg2.connect(**db_params)
except:
    sys.exit("Cannot connect to database.")
db_cur = db_conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

def fn_pgexec(sql, mode):
    if gl_debug is True:
        print(sql)
    if mode == "r":
        try:
            db_cur.execute(sql)
            if db_cur.rowcount == 0:
                print("DB query result is empty!")
                return None
            else:
                db_result = db_cur.fetchall()
                return db_result
        except:
            print("Cannot execute SQL statement.")
    if mode == "w":
        try:
            db_cur.execute(sql)
            db_conn.commit()
        except:
            print("Cannot write to DB.")

def fn_updateElevations():
    martin = os.path.expanduser("~martin")
    fg_home = os.path.join(martin, "terragear")
    fg_root = os.path.join(martin, "SCM", "FlightGear", "fgdata")
    fg_scenery = os.path.join(fgscenery, "Terrascenery")
    fgelev = os.path.join(martin, "bin", "fgelev")

    fgenv = dict(os.environ)
    fgenv["FG_HOME"] = fg_home
    fgenv["FG_ROOT"] = fg_root
    fgenv["FG_SCENERY"] = fg_scenery

    sql = "UPDATE fgs_objects SET ob_elevoffset = NULL where ob_elevoffset = 0;"
    fn_pgexec(sql, "w")

    sql = "SELECT COUNT(*) FROM fgs_objects WHERE ob_gndelev = -9999;"
    db_result = fn_pgexec(sql, "r")
    if db_result[0][0] > 0:
        # "fgelev" input:
        # 512280 -179.880556 -16.688333
        # "fgelev" output:
        # 512280: 19.479
        sql = "SELECT ob_id, ST_X(wkb_geometry), ST_Y(wkb_geometry) \
            FROM fgs_objects \
            WHERE ob_valid IS true AND ob_gndelev = -9999 \
            ORDER BY ob_tile, ob_id \
            LIMIT 1000;"
        db_result = fn_pgexec(sql, "r")
        num_rows = len(db_result)
        print("Updating %s object(s)" % num_rows)
        ePipe = Popen(["nice", "-n", "19", fgelev], env=fgenv, stdin=PIPE, stdout=PIPE, stderr=STDOUT)
        for row in db_result:
            obj = "%s %s %s\n" % (row['ob_id'],row['st_x'],row['st_y'])
            ePipe.stdin.write(obj)
            eResult = ePipe.stdout.readline()
            eList = eResult.translate(None, ":").split()
            sql = "UPDATE fgs_objects \
                SET ob_gndelev = %s \
                WHERE ob_id = %s AND ob_gndelev = -9999;" % (eList[1], eList[0])  # (ob_gndelev, ob_id)
            fn_pgexec(sql, "w")
    else:
        print("No elevations pending update")

def flform(field, val, ob_id):
    """Strip trailing zeroes/dots from floats, used in .stg-export"""
    try:
        return ('%.9f' % val).rstrip('0').rstrip('.')
    except:
        print("ERROR in Object: %s, field: %s, value: %s" % (ob_id, field, val))

def fn_exportCommon():
    global gl_sqlPosition, gl_sqlMeta, gl_sqlWhere, gl_sqlOrder
    sql = "SELECT DISTINCT fn_BoundingBox(wkb_geometry) AS bbox \
         FROM fgs_objects \
         WHERE fgs_objects.ob_modified > (SELECT stamp FROM fgs_timestamp WHERE fgs_timestamp.id = 0) \
         AND fgs_objects.ob_modified < (SELECT stamp FROM fgs_timestamp WHERE fgs_timestamp.id = 1) \
         ORDER BY bbox;"
    db_result = fn_pgexec(sql, "r")
    if db_result != None:
        num_rows = len(db_result)
        # Need to identify "bbox" column
        for row in db_result:
            bbox = "SELECT ST_AsText(wkb_geometry) as geom \
                FROM fgs_objects \
                WHERE fgs_objects.wkb_geometry && %s \
                ORDER BY geom;" % row['bbox']
            print(bbox)
    sqlPath = "SELECT DISTINCT concat('Objects/', fn_SceneDir(wkb_geometry), '/', fn_SceneSubDir(wkb_geometry), '/') AS obpath"
    sql = "%s FROM fgs_objects \
           UNION \
           %s FROM fgs_signs \
           ORDER BY obpath;" % (sqlPath, sqlPath)
    db_result = fn_pgexec(sql, "r")
    for row in db_result:
        os.makedirs(row['obpath'])
    print("Objects directories done")

    gl_sqlPosition = "concat('Objects/', fn_SceneDir(wkb_geometry), '/', fn_SceneSubDir(wkb_geometry), '/') AS obpath, \
        ST_Y(wkb_geometry) AS lat, ST_X(wkb_geometry) AS lon";
    gl_sqlMeta = "ob_tile AS tile, fn_StgElevation(ob_gndelev, ob_elevoffset)::float AS stgelev, \
        fn_StgHeading(ob_heading)::float AS stgheading, mo_id, mo_path";
    gl_sqlWhere = "WHERE ob_valid IS TRUE AND ob_tile IS NOT NULL \
        AND ob_model = mo_id AND ob_gndelev > -9999 AND mo_shared";
    gl_sqlOrder = "ORDER BY tile, mo_id, lon, lat, stgelev, stgheading";

def fn_exportShared():
    sql = "SELECT ob_id, %s, %s, mg_path \
        FROM fgs_objects, fgs_models, fgs_modelgroups %s > 0 AND mo_shared = mg_id %s;" % (gl_sqlPosition, gl_sqlMeta, gl_sqlWhere, gl_sqlOrder)
    db_result = fn_pgexec(sql, "r")
    suffix = ".stg"
    prevtile = -1;
    stgobj = None
    for row in db_result:
        obpath = os.path.join(workdir, row['obpath'])
        obtile = row['tile']  # integer !
        mopath = "SHARED Models/%s%s" % (row['mg_path'], row['mo_path'])
        stgrow = "%s%s %s %s %s %s\n" % ("OBJECT_", mopath, flform("lon", row['lon'], row['ob_id']), flform("lat", row['lat'], row['ob_id']), flform("stgelev", row['stgelev'], row['ob_id']), flform("stgheading", row['stgheading'], row['ob_id']))
        if obtile != prevtile:
            if prevtile > 0:
                stgobj.close()
            stgfile = os.path.join(obpath, str(obtile) + suffix)
            stgobj = open(stgfile, "a")
        stgobj.write(stgrow)
    stgobj.close()
    print("Shared Objects done")

def fn_exportStatic():
    sql = "SELECT ob_id, %s, %s, LENGTH(mo_modelfile) AS mo_size, mo_modelfile \
        FROM fgs_objects, fgs_models %s = 0 %s;" % (gl_sqlPosition, gl_sqlMeta, gl_sqlWhere, gl_sqlOrder)
    db_result = fn_pgexec(sql, "r")
    suffix = ".stg"
    prevtile = -1;
    stgobj = None
    for row in db_result:
        obpath = os.path.join(workdir, row['obpath'])
        obtile = row['tile']  # integer !
        mopath = "STATIC %s" % row['mo_path']
        stgrow = "%s%s %s %s %s %s\n" % ("OBJECT_", mopath, flform("lon", row['lon'], row['ob_id']), flform("lat", row['lat'], row['ob_id']), flform("stgelev", row['stgelev'], row['ob_id']), flform("stgheading", row['stgheading'], row['ob_id']))
        if obtile != prevtile:
            if prevtile > 0:
                stgobj.close()
            stgfile = os.path.join(obpath, str(obtile) + suffix)
            stgobj = open(stgfile, "a")
        stgobj.write(stgrow)
        if row['mo_size'] > 15:
            modeldata = base64.b64decode(row['mo_modelfile'])
            tarobject = io.BytesIO(modeldata)
            modeltar = tarfile.open(fileobj=tarobject, mode='r')
            modeltar.extractall(path=obpath)
    stgobj.close()
    print("Static Objects done")

def fn_exportSigns():
    gl_sqlMeta = "si_tile AS tile, si_gndelev::float AS stgelev, \
        fn_StgHeading(si_heading)::float AS stgheading";
    gl_sqlWhere = "WHERE si_valid IS TRUE";
    gl_sqlOrder = "ORDER BY tile, lon, lat, stgelev, stgheading";
    sql = "SELECT si_id, %s, %s, si_definition \
        FROM fgs_signs %s %s;" % (gl_sqlPosition, gl_sqlMeta, gl_sqlWhere, gl_sqlOrder)
    db_result = fn_pgexec(sql, "r")
    suffix = ".stg"
    prevtile = -1;
    for row in db_result:
        sipath = os.path.join(workdir, row['obpath'])
        sitile = row['tile']  # integer !
        mopath = "SIGN %s" % row['si_definition']
        stgrow = "%s%s %s %s %s %s\n" % ("OBJECT_", mopath, flform("lon", row['lon'], row['si_id']), flform("lat", row['lat'], row['si_id']), flform("stgelev", row['stgelev'], row['si_id']), flform("stgheading", row['stgheading'], row['si_id']))
        if sitile != prevtile:
            if prevtile > 0:
                stgobj.close()
            stgfile = os.path.join(sipath, str(sitile) + suffix)
            stgobj = open(stgfile, "a")
        stgobj.write(stgrow)
    stgobj.close()
    print("Signs done")

def fn_exportModels():
    sql = "SELECT DISTINCT concat('Models/', g.mg_path) AS mgpath \
        FROM fgs_models AS m, fgs_modelgroups AS g \
        WHERE m.mo_shared > 0 AND m.mo_shared = g.mg_id \
        ORDER BY mgpath;"
    db_result = fn_pgexec(sql, "r")
    for row in db_result:
        os.makedirs(row['mgpath'])
    print("Models directories done")

    sql = "SELECT m.mo_id, concat('Models/', g.mg_path) AS mgpath, \
        LENGTH(m.mo_modelfile) AS mo_size, m.mo_modelfile, g.mg_path \
        FROM fgs_models AS m, fgs_modelgroups AS g \
        WHERE m.mo_shared > 0 AND m.mo_shared = g.mg_id \
        ORDER BY g.mg_id, m.mo_id;"
    db_result = fn_pgexec(sql, "r")
    for row in db_result:
        modeldata = base64.b64decode(row['mo_modelfile'])
        mgpath = os.path.join(workdir, row['mgpath'])
        tarobject = io.BytesIO(modeldata)
        modeltar = tarfile.open(fileobj=tarobject, mode='r')
        modeltar.extractall(path=mgpath)
    print("Models done")

def fn_tfreset(tarinfo):
    tarinfo.uid = tarinfo.gid = 0
    tarinfo.uname = tarinfo.gname = "root"
    return tarinfo

def fn_pack():
    objects = os.path.join(workdir, "Objects")
    models = os.path.join(workdir, "Models")
    download = os.path.join(fgscenery, "Download")
    suffix = ".tgz"
    # 10x10 degree tile Objects
    for packtile in os.listdir(objects):
        if fnmatch.fnmatch(packtile, "[ew][0-9][0-9]0[ns][0-9]0"):
            destfile = os.path.join(download, packtile + suffix)
            packfile = tarfile.open(destfile, "w:gz", format=tarfile.USTAR_FORMAT)
            packfile.add("Objects/" + packtile, filter=fn_tfreset)
            packfile.close()
    # GlobalObjects
    destfile = os.path.join(download, "GlobalObjects" + suffix)
    packfile = tarfile.open(destfile, "w:gz", format=tarfile.USTAR_FORMAT)
    packfile.add("Objects", filter=fn_tfreset)
    packfile.close()
    # SharedModels
    destfile = os.path.join(download, "SharedModels" + suffix)
    packfile = tarfile.open(destfile, "w:gz", format=tarfile.USTAR_FORMAT)
    packfile.add("Models", filter=fn_tfreset)
    packfile.close()

# End of update period for current export
sql = "INSERT INTO fgs_timestamp (id, stamp) VALUES (1, now());"
fn_pgexec(sql, "w")

# Dirs to export
sql = "SELECT DISTINCT fn_SceneDir(wkb_geometry) AS dir \
    FROM fgs_objects \
    WHERE fgs_objects.ob_modified > (SELECT stamp FROM fgs_timestamp WHERE fgs_timestamp.id = 0) \
    AND fgs_objects.ob_modified < (SELECT stamp FROM fgs_timestamp WHERE fgs_timestamp.id = 1) \
    ORDER BY dir;"
db_result = fn_pgexec(sql, "r")

# Objects without valid tile numbers are ignored upon export
print("### Updating tile numbers ....")
sql = "UPDATE fgs_objects SET ob_tile = fn_GetTileNumber(wkb_geometry) \
    WHERE ob_tile < 1 OR ob_tile IS NULL;"
fn_pgexec(sql, "w")
sql = "UPDATE fgs_signs SET si_tile = fn_GetTileNumber(wkb_geometry) \
    WHERE si_tile < 1 OR si_tile IS NULL;"
fn_pgexec(sql, "w")

print("### Updating ground elevations ....")
fn_updateElevations()

# Cleanup Objects and Models
try:
    subprocess.check_call("find Objects/ Models/ -maxdepth 1 -mindepth 1 -exec rm -rf {} \;", shell=True)
except:
    sys.exit("Cleanup failed")

# Export the Objects directory
print("### Exporting Objects tree ....")
fn_exportCommon()
try:
    fn_exportShared()
except:
    sys.exit("Shared Objects export failed.")
try:
    fn_exportStatic()
except:
    sys.exit("Static Objects export failed.")
try:
    fn_exportSigns()
except:
    sys.exit("Signs export failed.")

# Export the Models directory
print("### Exporting Models tree ....")
try:
    fn_exportModels()
except:
    sys.exit("Models export failed.")

try:
    # Remove empty dirs
    subprocess.check_call("find Objects/ Models/ -depth -type d -empty -exec rmdir --ignore-fail-on-non-empty {} \;", shell=True)
except:
    sys.exit("Removing empy directories failed.")
try:
    # Ensure perms are correct
    subprocess.check_call("find Objects/ Models/ -type d -not -perm 755 -exec chmod 755 {} \;", shell=True)
    subprocess.check_call("find Objects/ Models/ -type f -not -perm 644 -exec chmod 644 {} \;", shell=True)
except:
    sys.exit("Set permissions failed.")

print("### Packing Global Objects and Models ....")
fn_pack()

# Requires major fixing before use !
#./download-map.pl

# Start of new update period
sql = "DELETE FROM fgs_timestamp WHERE id = 0;"
fn_pgexec(sql, "w")
sql = "UPDATE fgs_timestamp SET id = 0 WHERE id = 1;"
fn_pgexec(sql, "w")

# Cleaning up after interrupted export:
#  psql -c "DELETE FROM fgs_timestamp WHERE id = 1;"

statusfile.write("successful\n")
statusfile.flush()

Notice = "Subject: Export Finished"
Recipient = "martin@localhost"

mailPipe = Popen(["/usr/sbin/sendmail", "-bm", "-oi", Recipient], stdin=PIPE, stdout=PIPE, stderr=STDOUT)
mailStdout = mailPipe.communicate(input=str(Notice))[0]

db_cur.close
db_conn.close

statusfile.close()

# EOF
