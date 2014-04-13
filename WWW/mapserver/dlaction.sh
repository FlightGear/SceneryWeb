#!/bin/bash
#
# called by '<basename>.psp'
#
# Supply bounding box for 'pgsql2shp' as:
#   <xmin> <ymin>,<xmax> <ymax>

UUID=${1}
PGHOST=localhost
PGUSER=webuser
PGDATABASE=landcover
PSQL="psql -h ${PGHOST} -d ${PGDATABASE} -U webuser -tA -c"
BASEDIR=/home/fgscenery
PGSQL2SHP=/home/martin/bin/pgsql2shp
DUMPDIR=${BASEDIR}/SHPdump/${UUID}
DLDIR=${BASEDIR}/SHPdl

Feature() {
    ${PSQL} "SELECT selection FROM download \
        WHERE uuid = '${UUID}'"
}

DumpTable() {
    ${PSQL} "SELECT COALESCE( \
        (SELECT c.pgislayer FROM download AS d, conf_layer AS c \
            WHERE c.maplayer = d.selection AND d.uuid = '${UUID}'), \
        (SELECT selection FROM download \
            WHERE uuid = '${UUID}'));"
}

SQLFilter () {
    ${PSQL} "SELECT CASE \
        WHEN count(c.pgislayer) = 1 THEN concat('AND ', c.sqlfilter) \
        ELSE NULL END \
        FROM conf_layer AS c, download AS d \
        WHERE d.selection = c.maplayer \
        AND d.uuid = '${UUID}' \
        GROUP BY c.sqlfilter;"
}

DumpSingleLayer() {
    ${PGSQL2SHP} -f ${DUMPDIR}/${1}.shp \
        -h ${PGHOST} -u ${PGUSER} -g wkb_geometry -b -r ${PGDATABASE} \
        "SELECT * FROM ${2} \
            WHERE wkb_geometry && \
            (SELECT wkb_geometry FROM download WHERE uuid = '${UUID}') ${3}"
}

mkdir -p ${DUMPDIR} && cd ${DUMPDIR}/ || exit 1
rm -f *

FEATURE=`Feature`
DUMPTABLE=`DumpTable`
SQLFILTER=`SQLFilter`

DumpSingleLayer ${FEATURE} ${DUMPTABLE} "${SQLFILTER}"

cp -a ${BASEDIR}/WWW/mapserver/EPSG4326.prj ${DUMPDIR}/${FEATURE}\.prj

cp -a ${BASEDIR}/WWW/mapserver/COPYING.gplv2 ${DUMPDIR}/COPYING

zip ${DLDIR}/`Feature`-${UUID}\.zip `Feature`.*
cd ${DUMPDIR}/.. && rm -rf ${UUID}

# EOF
