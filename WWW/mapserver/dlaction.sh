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

HasMapLayer() {
    ${PSQL} "SELECT count(conf_layer.pgislayer) FROM conf_layer, download \
        WHERE download.uuid = '${UUID}' and download.selection = conf_layer.maplayer"
}

LayerSelect() {
    if [ `HasMapLayer` = 0 ]; then
        ${PSQL} "SELECT conf_layer.pgislayer FROM conf_layer, download \
            WHERE download.uuid = '${UUID}' and download.selection = conf_layer.pgislayer"
    elif [ `HasMapLayer` = 1 ]; then
        ${PSQL} "SELECT conf_layer.pgislayer FROM conf_layer, download \
            WHERE download.uuid = '${UUID}' and download.selection = conf_layer.maplayer"
  fi
}

if [ `HasMapLayer` = 1 ]; then
    SQLFILTER="AND `${PSQL} "SELECT conf_layer.sqlfilter FROM conf_layer, download \
        WHERE download.uuid = '${UUID}' and download.selection = conf_layer.maplayer"`"
else
    SQLFILTER=""
fi

DumpSingleLayer() {
    ${PGSQL2SHP} -f ${DUMPDIR}/${1}.shp \
        -h ${PGHOST} -u ${PGUSER} -g wkb_geometry -b -r ${PGDATABASE} \
        "SELECT * FROM ${1} \
            WHERE wkb_geometry && \
            (SELECT wkb_geometry FROM download WHERE uuid = '${UUID}') ${SQLFILTER}"
}

Prefix() {
    ${PSQL} "SELECT selection FROM download \
        WHERE uuid = '${UUID}'"
}

mkdir -p ${DUMPDIR} && cd ${DUMPDIR}/ || exit 1
rm -f *

PGISLAYER=`LayerSelect`

DumpSingleLayer ${PGISLAYER}

cp -a ${BASEDIR}/WWW/mapserver/EPSG4326.prj ${DUMPDIR}/${PGISLAYER}\.prj

cp -a ${BASEDIR}/WWW/mapserver/COPYING.gplv2 ${DUMPDIR}/COPYING

zip ${DLDIR}/`Prefix`-${UUID}\.zip `Prefix`.*
cd ${DUMPDIR}/.. && rm -rf ${UUID}

# EOF
