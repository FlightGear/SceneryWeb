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
import hashlib
import tarfile

sys.stdout = os.fdopen(sys.stdout.fileno(), "w", 0)

homedir = os.path.expanduser("~")  # jstockill
fgscenery = os.path.expanduser("~fgscenery")
martin = os.path.expanduser("~martin")
statusfile = open(os.path.join(homedir, ".exportstatus"), "w")
workdir = os.path.join(fgscenery, "Dump")
fg_scenery = os.path.join(fgscenery, "Terrascenery")
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

gl_debug = False  # FIXME
check_svn = False  # FIXME

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
    fg_home = os.path.join(martin, "terragear")
    fg_root = os.path.join(martin, "SCM", "FlightGear", "fgdata")
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
    sqlPath = "concat('Objects/', fn_SceneDir(wkb_geometry), '/', fn_SceneSubDir(wkb_geometry), '/') AS obpath"
    sql = "SELECT DISTINCT %s FROM fgs_objects \
           UNION \
           SELECT DISTINCT %s FROM fgs_signs \
           ORDER BY obpath;" % (sqlPath, sqlPath)
    db_result = fn_pgexec(sql, "r")
    for row in db_result:
        os.makedirs(row['obpath'])
    print("Objects directories done")

def fn_exportStaticModels():
    sql = "SELECT concat('Objects/', fn_SceneDir(wkb_geometry), '/', fn_SceneSubDir(wkb_geometry), '/') AS obpath, \
            mo_modelfile \
        FROM fgs_objects, fgs_models \
        WHERE LENGTH(mo_modelfile) > 15 \
            AND ob_valid IS TRUE AND ob_tile IS NOT NULL \
            AND ob_model = mo_id AND ob_gndelev > -9999 AND mo_shared = 0 \
        ORDER BY ob_tile;"
    db_result = fn_pgexec(sql, "r")
    for row in db_result:
        obpath = os.path.join(workdir, row['obpath'])
        modeldata = base64.b64decode(row['mo_modelfile'])
        tarobject = io.BytesIO(modeldata)
        modeltar = tarfile.open(fileobj=tarobject, mode='r')
        modeltar.extractall(path=obpath)
    print("Static Models done")

def fn_exportSharedModels():
    sql = "SELECT DISTINCT concat('Models/', g.mg_path) AS mgpath \
        FROM fgs_models AS m, fgs_modelgroups AS g \
        WHERE m.mo_shared > 0 AND m.mo_shared = g.mg_id \
        ORDER BY mgpath;"
    db_result = fn_pgexec(sql, "r")
    for row in db_result:
        os.makedirs(row['mgpath'])
    print("Models directories done")

    sql = "SELECT m.mo_id, concat('Models/', g.mg_path) AS mgpath, \
            m.mo_modelfile, g.mg_path \
        FROM fgs_models AS m, fgs_modelgroups AS g \
        WHERE LENGTH(mo_modelfile) > 15 \
            AND m.mo_shared > 0 AND m.mo_shared = g.mg_id \
        ORDER BY g.mg_id, m.mo_id;"
    db_result = fn_pgexec(sql, "r")
    for row in db_result:
        modeldata = base64.b64decode(row['mo_modelfile'])
        mgpath = os.path.join(workdir, row['mgpath'])
        tarobject = io.BytesIO(modeldata)
        modeltar = tarfile.open(fileobj=tarobject, mode='r')
        modeltar.extractall(path=mgpath)
    print("Shared Models done")

def fn_exportStgRows():
    sqlPath = "concat('Objects/', fn_SceneDir(wkb_geometry), '/', fn_SceneSubDir(wkb_geometry), '/') AS obpath"
    sql = "SELECT DISTINCT ob_tile AS tile, %s FROM fgs_objects \
        UNION \
        SELECT DISTINCT si_tile AS tile, %s FROM fgs_signs \
        ORDER BY tile;" % (sqlPath, sqlPath)
    db_result = fn_pgexec(sql, "r")
    num_rows = len(db_result)
    print("%s valid .stg-files") % num_rows
    for row in db_result:
        obtile = row['tile']  # integer !
        stgfile = "%s.stg" % obtile
        stgsql = "SELECT fn_DumpStgRows(%s);" % obtile
        db_stg = fn_pgexec(stgsql, "r")
        if db_stg != None:
            stgfullpath = os.path.join(workdir, row['obpath'], stgfile)
#            print("\n%s") % stgfullpath
            stgobj = open(stgfullpath, "a")
            stgstring = ("%s\n" % db_stg[0][0])
#            print(stgstring)
            md5sum = hashlib.md5(stgstring).hexdigest()
#            print(md5sum)
            stgobj.write(stgstring)
            stgobj.close()
            if check_svn is True:
                stgfullpath_svn = os.path.join(fg_scenery, row['obpath'], str(obtile) + suffix)
                print("\n%s") % stgfullpath_svn
                try:
                    print("Opening .stg-file %s") % stgfullpath_svn
                    stgobj_svn = open(stgfullpath_svn, "r")
                except:
                    sys.exit("Failed to open .stg-file %s") % stgfullpath_svn
                try:
                    print(("Reading .stg-file %s from:\n") % (stgfullpath_svn, stgobj_svn))
                    stgstring_svn = stgobj_svn.read()
                except:
                    sys.exit("Failed to read .stg-file %s") % stgfullpath_svn
                print(stgstring_svn)
                md5sum_svn = hashlib.md5(stgstring_svn).hexdigest()
                print(md5sum_svn)
                stgobj_svn.close()
    print("Stg-Rows done")

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

print("### Creating Objects directories ....")
fn_exportCommon()
#print("### Exporting Static Models ....")
#try:
#    fn_exportStaticModels()
#except:
#    sys.exit("Static Models export failed.")
#print("### Exporting Shared Models tree ....")
#try:
#    fn_exportSharedModels()
#except:
#    sys.exit("Shared Models export failed.")
print("### Exporting Objects tree ....")
try:
    fn_exportStgRows()
except:
    sys.exit("Stg-Rows export failed.")

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
