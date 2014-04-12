#!/bin/sh
#
# called by 'dlaction.psp'
#
# Supply bounding box for 'pgsql2shp' as:
#   <xmin> <ymin>,<xmax> <ymax>

UUID=${1}
PGHOST=localhost
PGUSER=webuser
PGDATABASE=landcover
PSQL="psql -h ${PGHOST} -d ${PGDATABASE} -U webuser -tA"
BASEDIR=/home/fgscenery
PGSQL2SHP=/home/martin/bin/pgsql2shp
DUMPDIR=${BASEDIR}/SHPdump/${UUID}
DLDIR=${BASEDIR}/SHPdl

GeomSelect() {
  ${PSQL} -c "SELECT ST_AsText(${1}_geometry) FROM download \
    WHERE uuid = '${UUID}'" | cut -f 2 -d \( | cut -f 1 -d \)
}

HasMapLayer() {
  ${PSQL} -c "SELECT count(conf_layer.pgislayer) FROM conf_layer, download \
    WHERE download.uuid = '${UUID}' and download.pgislayer = conf_layer.maplayer"
}

LayerSelect() {
  if [ `HasMapLayer` = 0 ]; then
    ${PSQL} -c "SELECT conf_layer.pgislayer FROM conf_layer, download \
      WHERE download.uuid = '${UUID}' and download.pgislayer = conf_layer.pgislayer"
  elif [ `HasMapLayer` = 1 ]; then
    ${PSQL} -c "SELECT conf_layer.pgislayer FROM conf_layer, download \
      WHERE download.uuid = '${UUID}' and download.pgislayer = conf_layer.maplayer"
  fi
}

if [ `HasMapLayer` = 1 ]; then
  SQLFILTER="AND `${PSQL} -c "SELECT conf_layer.sqlfilter FROM conf_layer, download \
    WHERE download.uuid = '${UUID}' and download.pgislayer = conf_layer.maplayer"`"
else
  SQLFILTER=""
fi

FileName() {
  ${PSQL} -c "SELECT pgislayer FROM download \
    WHERE uuid = '${UUID}'"
}

LL_GEOMETRY=`GeomSelect ll`
UR_GEOMETRY=`GeomSelect ur`
LAYER=`LayerSelect`
BBOX="${LL_GEOMETRY}, ${UR_GEOMETRY}"

echo ${LAYER}
echo ${BBOX}
HasMapLayer
echo ${SQLFILTER}
FileName

mkdir -p ${DUMPDIR} && cd ${DUMPDIR}/ || exit 1
rm -f *

${PGSQL2SHP} -f ${DUMPDIR}/${LAYER}.shp \
    -h ${PGHOST} -u ${PGUSER} -g wkb_geometry -b -r ${PGDATABASE} \
    "SELECT * FROM ${LAYER} \
        WHERE wkb_geometry && \
        ST_SetSRID('BOX3D(${BBOX})'::BOX3D, 4326 ) ${SQLFILTER}"

cp -a ${BASEDIR}/WWW/mapserver/EPSG4326.prj ${DUMPDIR}/${LAYER}\.prj

cp -a ${BASEDIR}/WWW/mapserver/COPYING.gplv2 ${DUMPDIR}/COPYING

zip ${DLDIR}/`FileName`-${UUID}\.zip `FileName`*
cd ${DUMPDIR}/.. && rm -rf ${UUID}
# EOF
