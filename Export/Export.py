#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os
import psycopg2
import subprocess

db_params = {"host":"geoscope.optiputer.net", "database":"landcover", "user":"webuser"}

homedir = os.path.expanduser("~")
statusfile = open(os.path.join(homedir, ".exportstatus"), 'w')
basedir = os.path.dirname(os.path.realpath(__file__))
statusfile.write("running\n")

try:
    os.chdir(basedir)
except:
    sys.exit()

try:
    db_conn = psycopg2.connect(**db_params)
except:
    print "Cannot connect to database."
db_cur = db_conn.cursor()


def fn_pgexec(sql, mode):
    if mode == 'r':
        try:
            db_cur.execute(sql)
            db_result = db_cur.fetchall()
            if db_result is None:
                print("DB query result is empty!"
            else:
                return db_result
        except:
            print("Cannot execute SQL statement.")
    if a_option == 'w':
        try:
            db_cur.execute(sql)
            db_conn.commit()
        except:
            print("Cannot write to DB.")

# End of update period for current export
sql = "INSERT INTO fgs_timestamp (id, stamp) VALUES (1, now());"
db_result = fn_pgexec(sql, 'w')

# Dirs to export
sql = "SELECT DISTINCT fn_SceneDir(wkb_geometry) AS dir \
    FROM fgs_objects \
    WHERE fgs_objects.ob_modified > (SELECT stamp FROM fgs_timestamp WHERE fgs_timestamp.id = 0) \
    AND fgs_objects.ob_modified < (SELECT stamp FROM fgs_timestamp WHERE fgs_timestamp.id = 1) \
    ORDER BY dir;"
db_result = fn_pgexec(sql, 'r')

# Objects without valid tile numbers are ignored upon export
print("### Updating tile numbers ....")
sql = "UPDATE fgs_objects SET ob_tile = fn_GetTileNumber(wkb_geometry) \
    WHERE ob_tile < 1 OR ob_tile IS NULL;"
db_result = fn_pgexec(sql, 'w')
sql = "UPDATE fgs_signs SET si_tile = fn_GetTileNumber(wkb_geometry) \
    WHERE si_tile < 1 OR si_tile IS NULL;"
db_result = fn_pgexec(sql, 'w')
print("### Updating ground elevations ....")
updateElevations = os.path.join(basedir, "updateElevations")
subprocess.check_call(["perl", updateElevations], shell=True)

# Cleanup Objects and Models
subprocess.check_call("find Objects/ Models/ -maxdepth 1 -mindepth 1 -exec rm -rf {} \;", shell=True)

# Export the Objects directory
print("### Exporting Objects tree ....")
exportObjects = os.path.join(basedir, "exportObjects")
subprocess.check_call(["perl", exportObjects], shell=True)

# Export the Models directory
print("### Exporting Models tree ....")
exportModels = os.path.join(basedir, "exportModels")
subprocess.check_call(["perl", exportModels], shell=True)

# Ensure perms are correct
subprocess.check_call("find Objects/ Models/ -type d -not -perm 755 -exec chmod 755 {} \;", shell=True)
subprocess.check_call("find Objects/ Models/ -type f -not -perm 644 -exec chmod 644 {} \;", shell=True)

# Disabled during World Scenery build preparations; Martin, 2010-01-22
print("### Packing Global Objects ....")
packObjects = os.path.join(basedir, "packObjects")
subprocess.check_call(["perl", packObjects], shell=True)

# Disabled during World Scenery build preparations; Martin, 2010-01-22
print("### Packing Global Models ....")
packModels = os.path.join(basedir, "packModels")
subprocess.check_call(["perl", packModels], shell=True)

# Requires major fixing before use !
#./download-map.pl

# Start of new update period
sql = "DELETE FROM fgs_timestamp WHERE id = 0;"
db_result = fn_pgexec(sql, 'w')
sql = "UPDATE fgs_timestamp SET id = 0 WHERE id = 1;"
db_result = fn_pgexec(sql, 'w')

# Cleaning up after interrupted export:
#  psql -c "DELETE FROM fgs_timestamp WHERE id = 1;"

Notice = MIMEText("Export Finished")
SMTPHandle = smtplib.SMTP('localhost')
Notice["Subject"] = "Export Finished"
Sender = "fgscenery@sphere"
Recipients = ["martin@localhost"]
Notice["From"] = Sender
Notice["To"] = Recipients[0]
SMTPHandle.sendmail(Sender, Recipients, Notice.as_string())
SMTPHandle.quit()

db_cur.close
db_conn.close

statusfile.close()

# EOF
