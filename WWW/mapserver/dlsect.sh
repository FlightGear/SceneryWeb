#!/bin/sh
#
# Copyright (C) 2012 - 2014 Martin Spott - Martin (at) flightgear (dot) org
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

# Download sector data for OpenRADAR; maprange x="3.0" y="3.0"
#
# Supply bounding box for 'pgsql2shp' as:
#   <xmin> <ymin>,<xmax> <ymax>
#

UUID=${1}
PGHOST=localhost
PGUSER=webuser
PGDATABASE=landcover
PSQL="psql -h ${PGHOST} -d ${PGDATABASE} -U webuser -tA -c"
BASEDIR=/home/fgscenery/GIT
PGSQL2SHP=/usr/bin/pgsql2shp
DUMPDIR=${BASEDIR}/../SHPdump/${UUID}
DLDIR=${BASEDIR}/../SHPdl

#GeomSelect() {
#  ${PSQL} "SELECT ST_AsText(${1}_geometry) FROM download \
#    WHERE uuid = '${UUID}'" | cut -f 2 -d \( | cut -f 1 -d \)
#}

#LL_GEOMETRY=`GeomSelect ll`
#UR_GEOMETRY=`GeomSelect ur`
#BBOX="${LL_GEOMETRY}, ${UR_GEOMETRY}"

mkdir -p ${DUMPDIR} && cd ${DUMPDIR}/ || exit 1
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
                ST_Buffer((SELECT wkb_geometry FROM v_apt_heading WHERE icao LIKE 'EHAM'), 2)"
        cp -a ${BASEDIR}/WWW/mapserver/EPSG4326.prj ${DUMPDIR}/${LAYER}\.prj
    fi
done

cp -a ${BASEDIR}/WWW/mapserver/COPYING.gplv2 ${DUMPDIR}/COPYING

zip ${DLDIR}/${UUID}\.zip *
cd ${DUMPDIR}/.. && rm -rf ${UUID}

# EOF
