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

import os, sys, io, errno

import psycopg2, psycopg2.extras
from subprocess import Popen, PIPE
import base64
import tarfile
import pysvn
import shutil
import re
import logging
import hashlib

sys.stdout = os.fdopen(sys.stdout.fileno(), "w", 0)

fg_scenery = "/home/terrascenery/checkout"

pghost = "localhost"
pgport = 5432
pgdatabase = "scenemodels"
pguser = "updateuser"
db_params = {"host":pghost, "port":pgport, "database":pgdatabase, "user":pguser}

pgenv = dict(os.environ)
pgenv["PGHOST"] = pghost
pgenv["PGDATABASE"] = pgdatabase
pgenv["PGUSER"] = pguser

gl_debug = False  # FIXME

def fn_error(*objs):
    print("DEBUG: ", objs)

def fn_debug(*objs):
    if gl_debug is True:
        fn_error(objs)

try:
    os.chdir(fg_scenery)
except:
    sys.exit("Cannot change into work dir.")

try:
    db_conn = psycopg2.connect(**db_params)
except Exception as e:
    logging.exception("connect to db")
    sys.exit("Cannot connect to database.")

db_cur = db_conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

def fn_pgexec(sql, mode):
    fn_debug(sql)
    if mode == "r":
        try:
            db_cur.execute(sql)
            if db_cur.rowcount == 0:
                fn_debug("DB query result is empty!")
                return None
            else:
                db_result = db_cur.fetchall()
                return db_result
        except:
            fn_error("Cannot execute SQL statement.")
    if mode == "w":
        try:
            db_cur.execute(sql)
            db_conn.commit()
        except:
            fn_error("Cannot write to DB.")

def mkdir_p(path):
    try:
        os.makedirs(path)
    except OSError as exc: # Python >2.5
        if exc.errno == errno.EEXIST and os.path.isdir(path):
            pass
        else: raise

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
    fn_error("Exporting %s models" % len(db_result))
    for row in db_result:
        moid = row['id']
        mopath = row['path']
        fullpath = os.path.join(fg_scenery, mopath)
        mkdir_p(fullpath)
        modeldata = base64.b64decode(row['modelfile'])
        tarobj = io.BytesIO(modeldata)
        modeltar = tarfile.open(fileobj=tarobj, mode='r:gz')
        modeltar.extractall(path=fullpath)
    fn_error("Models done")


def fn_md5_of_file(fname):
    hash = hashlib.md5()
    try:
        with open(fname, "rb") as f:
            for chunk in iter(lambda: f.read(4096), b""):
                hash.update(chunk)
    except:
        pass

    return hash.hexdigest()

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
    fn_error("%s valid .stg-files" % num_rows)
    for row in db_result:
        obpath = row['obpath']
        obtile = row['tile']  # integer !
        stgfile = "%s.stg" % obtile
        stgsql = "SELECT fn_DumpStgRows(%s);" % obtile
        db_stg = fn_pgexec(stgsql, "r")
        if db_stg != None:
            # create the directory
            stgfullpath = os.path.join(fg_scenery, obpath, stgfile)
            mkdir_p( os.path.join(fg_scenery, obpath))
            # the full stg file name
            stgfullpath = os.path.join(fg_scenery, obpath, stgfile)
            stgstring = "%s\n" % db_stg[0][0]
            # compare new content with file content
            newmd5 = hashlib.md5(stgstring).hexdigest()
            curmd5 = fn_md5_of_file(stgfullpath)
            if  newmd5 != curmd5:
                print stgfullpath
                stgobj = open(stgfullpath, "w")
                stgobj.write(stgstring)
                stgobj.close()

    fn_error("Stg-Files done")

print("### Exporting Models ....")
try:
    fn_exportModels()
except Exception as e:
    logging.exception("Models export")
    sys.exit("Models export failed.")

print("### Exporting Stg-Files ....")
try:
    fn_exportStgRows()
except Exception as e:
    logging.exception("Stg-Files export")
    sys.exit("Stg-Files export failed.")

db_cur.close
db_conn.close

# EOF
