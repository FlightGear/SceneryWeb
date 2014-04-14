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
BASEDIR=/home/fgscenery/GIT
PGSQL2SHP=/home/martin/bin/pgsql2shp
DUMPDIR=${BASEDIR}/../SHPdump/${UUID}
DLDIR=${BASEDIR}/../SHPdl

Feature() {
    ${PSQL} "SELECT selection FROM download \
        WHERE uuid = '${UUID}'"
}

DumpSingleLayer() {
    ${PGSQL2SHP} -f ${DUMPDIR}/${1}.shp \
        -h ${PGHOST} -u ${PGUSER} -g wkb_geometry -b -r ${PGDATABASE} \
        "SELECT * FROM ${1} \
            WHERE wkb_geometry && \
            (SELECT wkb_geometry FROM download WHERE uuid = '${UUID}') ${SQLFILTER}"
}

mkdir -p ${DUMPDIR} && cd ${DUMPDIR}/ || exit 1
rm -f *

FEATURE=`Feature`

for LAYER in `${PSQL} "SELECT fn_DlTable('${UUID}')"`; do
    DumpSingleLayer ${LAYER}
    cp -a ${BASEDIR}/WWW/mapserver/EPSG4326.prj ${DUMPDIR}/${LAYER}\.prj
done

cp -a ${BASEDIR}/WWW/mapserver/COPYING.gplv2 ${DUMPDIR}/COPYING

zip ${DLDIR}/`Feature`-${UUID}\.zip `Feature`_*.*
cd ${DUMPDIR}/.. && rm -rf ${UUID}

# EOF
