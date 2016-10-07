#!/bin/bash
# Update the statistics of the scenemodels database to terrascenery svn repository
# Written 2016 by Torsten Dreyer
# Released into the public domain
#
DBNAME=scenemodels
DBUSER=updateuser
DBHOST=localhost
PSQL="/usr/bin/psql -d ${DBNAME} -U ${DBUSER} -h ${DBHOST}"

${PSQL} --quiet  << EOF
  INSERT INTO fgs_statistics (st_objects,st_models,st_authors,st_navaids,st_signs,st_date) 
    WITH 
      t1 AS (SELECT count(*) objects FROM fgs_objects), 
      t2 AS (SELECT count(*) models FROM fgs_models), 
      t3 AS (SELECT count(*) authors FROM fgs_authors), 
      t4 AS (SELECT count(*) navaids FROM fgs_navaids),
      t5 AS (SELECT count(*) signs FROM fgs_signs) 
        SELECT objects,models,authors,navaids,signs,now() FROM t1,t2,t3,t4,t5
EOF
