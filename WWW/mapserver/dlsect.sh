#!/bin/sh
#
# Download sector data for OpenRADAR; maprange x="3.0" y="3.0"
#
# Supply bounding box for 'pgsql2shp' as:
#   <xmin> <ymin>,<xmax> <ymax>

UUID=${1}
PGHOST=geoscope.optiputer.net
PGDATABASE=landcover
PGUSER=webuser
PSQL="psql -h ${PGHOST} -d ${PGDATABASE} -U webuser -tA -c"
BASEDIR=/home/martin
PGSQL2SHP=/opt/PostgreSQL/bin/pgsql2shp
DUMPDIR=${BASEDIR}/SHPdump/${UUID}
DLDIR=${BASEDIR}/SHPdl

#GeomSelect() {
#  ${PSQL} "SELECT ST_AsText(${1}_geometry) FROM download \
#    WHERE uuid LIKE '${UUID}'" | cut -f 2 -d \( | cut -f 1 -d \)
#}

#LL_GEOMETRY=`GeomSelect ll`
#UR_GEOMETRY=`GeomSelect ur`
#BBOX="${LL_GEOMETRY}, ${UR_GEOMETRY}"

mkdir ${DUMPDIR} && cd ${DUMPDIR}/ || exit 1
rm -f *

for LAYER in apt_runway apt_tarmac v0_lake v0_landmass; do
    COUNT=`${PSQL} "SELECT COUNT(wkb_geometry) FROM ${LAYER} \
              WHERE wkb_geometry && \
              ST_SetSRID('BOX3D(${BBOX})'::BOX3D, 4326)"`
    if [ ${COUNT} -gt 0 ]; then
        ${PGSQL2SHP} -f ${DUMPDIR}/${LAYER}.shp \
            -h ${PGHOST} -u ${PGUSER} -g wkb_geometry -b -r ${PGDATABASE} \
            "SELECT * FROM ${LAYER} \
              WHERE wkb_geometry && \
              ST_Buffer((SELECT wkb_geometry FROM apt_airfield WHERE icao LIKE 'EHAM'), 2)"
        cp -a ${BASEDIR}/landcover/EPSG4326.prj ${DUMPDIR}/${LAYER}\.prj
    fi
done

zip ${DLDIR}/${UUID}\.zip *
cd ${DUMPDIR}/.. && rm -rf ${UUID}

# EOF
