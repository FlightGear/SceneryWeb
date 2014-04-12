#!/bin/sh
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
    WHERE download.uuid = '${UUID}' and download.pgislayer = conf_layer.maplayer"
}

LayerSelect() {
  if [ `HasMapLayer` = 0 ]; then
    ${PSQL} "SELECT conf_layer.pgislayer FROM conf_layer, download \
      WHERE download.uuid = '${UUID}' and download.pgislayer = conf_layer.pgislayer"
  elif [ `HasMapLayer` = 1 ]; then
    ${PSQL} "SELECT conf_layer.pgislayer FROM conf_layer, download \
      WHERE download.uuid = '${UUID}' and download.pgislayer = conf_layer.maplayer"
  fi
}

if [ `HasMapLayer` = 1 ]; then
  SQLFILTER="AND `${PSQL} "SELECT conf_layer.sqlfilter FROM conf_layer, download \
    WHERE download.uuid = '${UUID}' and download.pgislayer = conf_layer.maplayer"`"
else
  SQLFILTER=""
fi

FileName() {
  ${PSQL} "SELECT pgislayer FROM download \
    WHERE uuid = '${UUID}'"
}

PGISLAYER=`LayerSelect`

echo ${PGISLAYER}
HasMapLayer
echo ${SQLFILTER}
FileName

mkdir -p ${DUMPDIR} && cd ${DUMPDIR}/ || exit 1
rm -f *

${PGSQL2SHP} -f ${DUMPDIR}/${PGISLAYER}.shp \
    -h ${PGHOST} -u ${PGUSER} -g wkb_geometry -b -r ${PGDATABASE} \
    "SELECT * FROM ${PGISLAYER} \
        WHERE wkb_geometry && \
        (SELECT ST_AsText(wkb_geometry) FROM download WHERE uuid = '${UUID}') ${SQLFILTER}"

cp -a ${BASEDIR}/WWW/mapserver/EPSG4326.prj ${DUMPDIR}/${PGISLAYER}\.prj

cp -a ${BASEDIR}/WWW/mapserver/COPYING.gplv2 ${DUMPDIR}/COPYING

zip ${DLDIR}/`FileName`-${UUID}\.zip `FileName`*
cd ${DUMPDIR}/.. && rm -rf ${UUID}

# EOF
