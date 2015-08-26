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

import psycopg2, psycopg2.extras
from subprocess import check_call, Popen, PIPE, STDOUT
import base64
import fnmatch
import hashlib
import tarfile
import pysvn
from datetime import date
import shutil
import re

sys.stdout = os.fdopen(sys.stdout.fileno(), "w", 0)

martin = os.path.expanduser("~martin")
fgscenery = os.path.expanduser("~fgscenery")
statusfilepath = os.path.join(martin, ".exportstatus")
workdir = os.path.join(fgscenery, "Dump")
fg_scenery = os.path.join(fgscenery, "Terrascenery")
svn_root = "file://%s/SVN/terrascenery/trunk/data/Scenery" % martin

try:
    statusfile = open(statusfilepath, "r")
    if statusfile.readlines()[2].rstrip() != "successful":
        print("Previous Export didn't finish successfuly")
    statusfile.close()

except:
    statusfile = open(statusfilepath, "w")
    statusfile.write("running\n")
    statusfile.flush()

    pghost = "eclipse.optiputer.net"
    pgdatabase = "landcover"
    pguser = "martin"
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
    check_svn = True  # FIXME

    svn_newfiles = []
    svn_changefiles = []
    svn_syncdirs = []
    gl_diffcount = 0
    svnclient = pysvn.Client(os.path.join(martin, ".subversion"))

    svn_info = svnclient.info(fg_scenery)
    #print("SVN root: %s" % svn_info.url)

    datestr = date.today().strftime("%Y%m%d")
    #print("Date string: %s" % datestr)

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
                    if gl_debug is True:
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
        fg_root = os.path.join(martin, "live", "fgdata-3.0.0")
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
        # Create empty Objects/ and Models/ directory trees
        sqlPath = "concat('Objects/', fn_SceneDir(wkb_geometry), '/', fn_SceneSubDir(wkb_geometry), '/') AS path"
        sql = "SELECT DISTINCT %s FROM fgs_objects \
            UNION \
            SELECT DISTINCT %s FROM fgs_signs \
            UNION \
            SELECT DISTINCT concat('Models/', g.mg_path) AS path \
            FROM fgs_models AS m \
            LEFT JOIN fgs_modelgroups AS g \
                ON m.mo_shared = g.mg_id \
            WHERE m.mo_shared > 0 \
            ORDER BY path;" % (sqlPath, sqlPath)
        db_result = fn_pgexec(sql, "r")
        for row in db_result:
            os.makedirs(row['path'])
        print("Empty Objects and Models directories done")

    def fn_exportModels():
        sql = "SELECT m.mo_id AS id, concat('Objects/', fn_SceneDir(o.wkb_geometry), '/', fn_SceneSubDir(o.wkb_geometry), '/') AS path, \
                m.mo_modelfile AS modelfile \
            FROM fgs_objects AS o \
            LEFT JOIN fgs_models AS m \
                ON o.ob_model = m.mo_id \
            WHERE LENGTH(m.mo_modelfile) > 15 \
                AND o.ob_valid IS TRUE AND o.ob_tile IS NOT NULL \
                AND o.ob_gndelev > -9999 AND m.mo_shared = 0 \
            UNION \
            SELECT m.mo_id AS id, concat('Models/', g.mg_path) AS path, \
                m.mo_modelfile AS modelfile \
            FROM fgs_models AS m \
            LEFT JOIN fgs_modelgroups AS g \
                ON m.mo_shared = g.mg_id \
            WHERE LENGTH(m.mo_modelfile) > 15 \
                AND m.mo_shared > 0 \
            ORDER BY path, id;"
        db_result = fn_pgexec(sql, "r")
        for row in db_result:
            moid = row['id']
            mopath = row['path']
            fullpath = os.path.join(workdir, mopath)
            modeldata = base64.b64decode(row['modelfile'])
            tarobj = io.BytesIO(modeldata)
            modeltar = tarfile.open(fileobj=tarobj, mode='r:gz')
            modeltar.extractall(path=fullpath)
            for member in modeltar.getmembers():
                filename = member.name
                mofileobj = modeltar.extractfile(filename)
                try:
                    filedata = mofileobj.read()
                except:
                    print("Broken model archive: %s." % moid)
                else:
                    md5sum = hashlib.md5(filedata).hexdigest()
                    if check_svn is True:
                        fn_check_svn(mopath, filename, md5sum)
        print("Models done")

        sql = "SELECT DISTINCT m.mo_id, g.mg_path, m.mo_path AS fullpath \
            FROM fgs_objects AS o \
            LEFT JOIN fgs_models AS m \
                ON o.ob_model = m.mo_id \
            LEFT JOIN fgs_modelgroups AS g \
                ON m.mo_shared = g.mg_id \
            WHERE m.mo_shared > 0 \
                AND ST_Within(o.wkb_geometry, \
                    ST_GeomFromText('POLYGON((-123 37, -121 37, -121 38, -123 38, -123 37))', 4326)) \
            ORDER BY g.mg_path, m.mo_path;"

    def fn_check_svn(path, file, md5sum):
        '''
        Runs once per file.
        '''
        global gl_diffcount
        path = os.path.normpath(path)
        fullpath = os.path.join(path, file)
        svn_dirpath = os.path.join(fg_scenery, path)
        svn_fullpath = os.path.join(fg_scenery, fullpath)
        try:
            if gl_debug is True:
                print("Opening file %s" % svn_fullpath)
            svnobj = open(svn_fullpath, "rb")
        except:
            print("Failed to open file %s" % svn_fullpath)
            if not os.path.isdir(svn_dirpath):
                os.makedirs(svn_dirpath)
                svnclient.add(svn_dirpath)
            shutil.copy(fullpath, svn_dirpath)
            try:
                svnclient.add(svn_fullpath)
            except:
                print("Failed to add file %s to SVN" % svn_fullpath)
            svn_newfiles.append(fullpath)
            svn_syncdirs.append(path)
        else:
            try:
                if gl_debug is True:
                    print("File %s opened in access mode: %s,\nreading now ...." % (svnobj.name, svnobj.mode))
                svn_data = svnobj.read()
            except:
                print("Failed to read file %s" % svn_fullpath)
            else:
                svn_md5sum = hashlib.md5(svn_data).hexdigest()
                if md5sum != svn_md5sum:
                    shutil.copy(fullpath, svn_dirpath)
                    svn_changefiles.append(fullpath)
                    svn_syncdirs.append(path)
                    if 0:
                        svn_rfullpath = os.path.join(svn_root, fullpath)
                        try:
                            svn_rdata = svnclient.cat(svn_rfullpath)
                        except:
                            print("Failed to read from repository:\n    %s" % svn_rfullpath)
                        else:
                            svn_rmd5sum = hashlib.md5(svn_rdata).hexdigest()
                            print("    %s\n    Dump  : %s\n    Local : %s\n    Remote: %s" % (svn_rfullpath, md5sum, svn_md5sum, svn_rmd5sum))
                    gl_diffcount += 1
            svnobj.close()

    def fn_exportStgRows():
        '''
        Fetch the entire file content as a single string.
        '''
        sqlPath = "concat('Objects/', fn_SceneDir(wkb_geometry), '/', fn_SceneSubDir(wkb_geometry), '/') AS obpath"
        sql = "SELECT DISTINCT ob_tile AS tile, %s FROM fgs_objects \
            UNION \
            SELECT DISTINCT si_tile AS tile, %s FROM fgs_signs \
            ORDER BY tile;" % (sqlPath, sqlPath)
        db_result = fn_pgexec(sql, "r")
        num_rows = len(db_result)
        print("%s valid .stg-files" % num_rows)
        for row in db_result:
            obpath = row['obpath']
            obtile = row['tile']  # integer !
            stgfile = "%s.stg" % obtile
            stgsql = "SELECT fn_DumpStgRows(%s);" % obtile
            db_stg = fn_pgexec(stgsql, "r")
            if db_stg != None:
                stgfullpath = os.path.join(workdir, obpath, stgfile)
                stgobj = open(stgfullpath, "a")
                stgstring = "%s\n" % db_stg[0][0]
                md5sum = hashlib.md5(stgstring).hexdigest()
                stgobj.write(stgstring)
                stgobj.close()
                if check_svn is True:
                    fn_check_svn(obpath, stgfile, md5sum)
        print("Stg-Files done")

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
                distfilepath = os.path.join(download, packtile + suffix)
                distfileobj = tarfile.open(distfilepath, "w:gz", format=tarfile.USTAR_FORMAT)
                distfileobj.add("Objects/" + packtile, filter=fn_tfreset)
                distfileobj.close()
        # GlobalObjects
        distfilepath = os.path.join(download, "GlobalObjects" + suffix)
        distfileobj = tarfile.open(distfilepath, "w:gz", format=tarfile.USTAR_FORMAT)
        distfileobj.add("Objects", filter=fn_tfreset)
        distfileobj.close()
        # SharedModels
        distfilepath = os.path.join(download, "SharedModels" + suffix)
        distfileobj = tarfile.open(distfilepath, "w:gz", format=tarfile.USTAR_FORMAT)
        distfileobj.add("Models", filter=fn_tfreset)
        distfileobj.close()

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
    try:
        fn_updateElevations()
    except:
        sys.exit("Ground elevations failed.")

    # Cleanup Objects and Models
    try:
        check_call("find Objects/ Models/ -maxdepth 1 -mindepth 1 -exec rm -rf {} \;", shell=True)
    except:
        sys.exit("Cleanup failed.")
    # Start exports
    print("### Creating Objects directories ....")
    try:
        fn_exportCommon()
    except:
        sys.exit("Setting up export failed.")
    print("### Exporting Models ....")
    try:
        fn_exportModels()
    except:
        sys.exit("Models export failed.")
    print("### Exporting Stg-Files ....")
    try:
        fn_exportStgRows()
    except:
        sys.exit("Stg-Files export failed.")
    # All exports are over now, clean up
    try:
        # Remove empty dirs
        check_call("find Objects/ Models/ -depth -type d -empty -exec rmdir --ignore-fail-on-non-empty {} \;", shell=True)
    except:
        sys.exit("Removing empy directories failed.")
    try:
        # Ensure permissions are correct
        check_call("find Objects/ Models/ -type d -not -perm 755 -exec chmod 755 {} \;", shell=True)
        check_call("find Objects/ Models/ -type f -not -perm 644 -exec chmod 644 {} \;", shell=True)
    except:
        sys.exit("Set permissions failed.")
    # Export dirs are clean now, print status
    if gl_diffcount == 1:
        print("### 1 file changed")
    elif gl_diffcount > 1:
        print("### %s files changed" % gl_diffcount)

    num_newfiles = len(svn_newfiles)
    if num_newfiles > 0:
        svn_newlist = sorted(set(svn_newfiles))
        print("### Files unknown to SVN:")
        for newfile in svn_newlist:
            print("    %s" % newfile)

    num_changefiles = len(svn_changefiles)
    if num_changefiles > 0:
        svn_changelist = sorted(set(svn_changefiles))
        print("### Files differing from SVN")
        dupes = open(os.path.join(martin, "WWW", "dupes.txt"), "w")
        for changefile in svn_changelist:
            print("    %s" % changefile)
            if not re.match(r".*\.stg$", changefile):
                dupes.write("%s\n" % changefile)
        dupes.flush()
        dupes.close()

    num_syncdirs = len(svn_syncdirs)
    if num_syncdirs > 0:
        svn_synclist = sorted(set(svn_syncdirs), reverse=True)
        print("### Directories pending SVN commit")
        for syncdir in svn_synclist:
            syncdir = os.path.normpath(syncdir)
            print("    %s" % syncdir)
            svn_dirpath = os.path.join(fg_scenery, syncdir)
            splitpath = syncdir.split(os.sep)
            commitdir = os.path.join(splitpath[0], splitpath[1])
            commitmsg = "%s %s" % (commitdir, datestr)
            try:
                svnclient.checkin([os.path.join(fg_scenery, commitdir)], commitmsg)
            except:
                sys.exit("SVN commit failed in: %s" % commitdir)

    if (gl_diffcount == 0 and num_syncdirs > 0) or (gl_diffcount > 0 and num_syncdirs == 0):
        print("### Someting strange going on here .... ###")

    # Pack
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
