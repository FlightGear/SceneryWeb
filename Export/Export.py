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

import os, sys
import subprocess

import psycopg2
from subprocess import Popen, PIPE, STDOUT

sys.stdout = os.fdopen(sys.stdout.fileno(), "w", 0)

pghost = "geoscope.optiputer.net"
pgdatabase = "landcover"
pguser = "jstockill"

db_params = {"host":pghost, "database":pgdatabase, "user":pguser}

pgenv = dict(os.environ)
pgenv["PGHOST"] = pghost
pgenv["PGDATABASE"] = pgdatabase
pgenv["PGUSER"] = pguser

homedir = os.path.expanduser("~")
statusfile = open(os.path.join(homedir, ".exportstatus"), "w")
basedir = os.path.dirname(os.path.realpath(__file__))
workdir = "/home/fgscenery/Dump"
statusfile.write("running\n")
statusfile.flush()

try:
    os.chdir(workdir)
except:
    sys.exit("Cannot change into work dir.")

try:
    db_conn = psycopg2.connect(**db_params)
except:
    sys.exit("Cannot connect to database.")
db_cur = db_conn.cursor()

def fn_pgexec(sql, mode):
    if mode == "r":
        try:
            db_cur.execute(sql)
            db_result = db_cur.fetchall()
            if db_result is None:
                print("DB query result is empty!")
            else:
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
    martin = os.path.expanduser("/home/martin")
    fgscenery = os.path.expanduser("/home/fgscenery")
    fg_home = os.path.join(martin, "terragear")
    fg_root = os.path.join(martin, "SCM", "FlightGear", "fgdata")
    fg_scenery = os.path.join(fgscenery, "Terrascenery")
    fgelev = os.path.join(martin, "bin", "fgelev")

    fgenv = dict(os.environ)
    fgenv["FG_HOME"] = fg_home
    fgenv["FG_ROOT"] = fg_root
    fgenv["FG_SCENERY"] = fg_scenery

    sql = "UPDATE fgs_objects SET ob_elevoffset = NULL where ob_elevoffset = 0;"
    db_result = fn_pgexec(sql, "w")

    while 1:
        checksql = "SELECT COUNT(*) FROM fgs_objects WHERE ob_gndelev = -9999"
        db_result = fn_pgexec(checksql, "r")[0]
        if db_result > 0:
            # "fgelev" input:
            # 512280 -179.880556 -16.688333
            # "fgelev" output:
            # 512280: 19.479
            sql = "SELECT ob_id, ST_X(wkb_geometry), ST_Y(wkb_geometry) FROM fgs_objects WHERE ob_valid IS true AND ob_gndelev > -9999 ORDER BY ob_tile, ob_id LIMIT 3"
            db_result = fn_pgexec(sql, "r")
            num_rows = len(db_result)
            ePipe = Popen(fgelev, env=fgenv, stdin=PIPE, stdout=PIPE, stderr=STDOUT)
            for i in xrange(0, num_rows, 1):
                row = "%s %s %s\n" % (db_result[i][0],db_result[i][1],db_result[i][2])
                ePipe.stdin.write(row)
                eResult = ePipe.stdout.readline()
                eList = eResult.translate(None, ":").split()
                sql = "UPDATE fgs_objects SET ob_gndelev = %s WHERE ob_id = %s AND ob_gndelev = -9999;" % (eList[1], eList[0])  # (ob_gndelev, ob_id)
                db_result = fn_pgexec(sql, "w")
        else:
            print("No elevations pending update")
            break

# End of update period for current export
sql = "INSERT INTO fgs_timestamp (id, stamp) VALUES (1, now());"
db_result = fn_pgexec(sql, "w")

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
db_result = fn_pgexec(sql, "w")
sql = "UPDATE fgs_signs SET si_tile = fn_GetTileNumber(wkb_geometry) \
    WHERE si_tile < 1 OR si_tile IS NULL;"
db_result = fn_pgexec(sql, "w")
print("### Updating ground elevations ....")
updateElevations = os.path.join(basedir, "updateElevations")
subprocess.check_call(updateElevations, env=pgenv, shell=True)

try:
    # Cleanup Objects and Models
    subprocess.check_call("find Objects/ Models/ -maxdepth 1 -mindepth 1 -exec rm -rf {} \;", shell=True)
except:
    sys.exit("Cleanup failed")

try:
    # Export the Objects directory
    print("### Exporting Objects tree ....")
    exportObjects = os.path.join(basedir, "exportObjects")
    subprocess.check_call(exportObjects, env=pgenv, shell=True)
except:
    sys.exit("Objects export failed.")

try:
    # Export the Models directory
    print("### Exporting Models tree ....")
    exportModels = os.path.join(basedir, "exportModels")
    subprocess.check_call(exportModels, env=pgenv, shell=True)
except:
    sys.exit("Models export failed.")

try:
    # Ensure perms are correct
    subprocess.check_call("find Objects/ Models/ -type d -not -perm 755 -exec chmod 755 {} \;", shell=True)
    subprocess.check_call("find Objects/ Models/ -type f -not -perm 644 -exec chmod 644 {} \;", shell=True)
except:
    sys.exit("Set permissions failed.")

# Disabled during World Scenery build preparations; Martin, 2010-01-22
print("### Packing Global Objects ....")
packObjects = os.path.join(basedir, "packObjects")
subprocess.check_call(packObjects, shell=True)

# Disabled during World Scenery build preparations; Martin, 2010-01-22
print("### Packing Global Models ....")
packModels = os.path.join(basedir, "packModels")
subprocess.check_call(packModels, shell=True)

# Requires major fixing before use !
#./download-map.pl

# Start of new update period
sql = "DELETE FROM fgs_timestamp WHERE id = 0;"
db_result = fn_pgexec(sql, "w")
sql = "UPDATE fgs_timestamp SET id = 0 WHERE id = 1;"
db_result = fn_pgexec(sql, "w")

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
