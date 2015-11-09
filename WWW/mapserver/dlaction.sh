#!/bin/bash
#
# Copyright (C) 2012 - 2015 Martin Spott - Martin (at) flightgear (dot) org
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

# called by '<basename>.psp'
#
#    shpdl.psp -  dlsingle.psp \
#               \ dlshp.psp     - dlaction.sh
#
# Supply bounding box for 'pgsql2shp' as:
#   <xmin> <ymin>,<xmax> <ymax>
#

UUID=${1}
PGHOST=localhost
PGUSER=webuser
PGDATABASE=landcover
PSQL="psql -h ${PGHOST} -p 5433 -d ${PGDATABASE} -U webuser -tA -c"
BASEDIR=/home/fgscenery/GIT
PGSQL2SHP=/usr/bin/pgsql2shp
DUMPDIR=${BASEDIR}/../SHPdump/${UUID}
DLDIR=${BASEDIR}/../SHPdl

SINGLE=`${PSQL} "SELECT single FROM download WHERE uuid = '${UUID}'" | cut -c 1 | tr A-Z a-z`

FEATURE=`${PSQL} "SELECT feature FROM download WHERE uuid = '${UUID}'"`

DumpTable() {
    ${PSQL} "SELECT COALESCE( \
        (SELECT c.pgislayer FROM download AS d, conf_layer AS c \
            WHERE c.maplayer = d.feature AND d.uuid = '${UUID}'), \
        (SELECT feature FROM download \
            WHERE uuid = '${UUID}'));"
}
DUMPTABLE=`DumpTable`

SQLFilter () {
    ${PSQL} "SELECT CASE \
        WHEN count(c.pgislayer) = 1 THEN concat('AND ', c.sqlfilter) \
        ELSE NULL END \
        FROM conf_layer AS c, download AS d \
        WHERE d.feature = c.maplayer \
        AND d.uuid = '${UUID}' \
        GROUP BY c.sqlfilter;"
}
SQLFILTER=`SQLFilter`

DumpSingleLayer() {
    if [ -z ${2} ]; then
        TABLE=${1}
    else
        TABLE=${2}
    fi
    ${PGSQL2SHP} -f ${DUMPDIR}/${1}.shp \
        -h ${PGHOST} -u ${PGUSER} -g wkb_geometry -b -r ${PGDATABASE} \
        "SELECT * FROM ${TABLE} \
            WHERE wkb_geometry && \
            (SELECT wkb_geometry FROM download WHERE uuid = '${UUID}') ${3}"
}

mkdir -p ${DUMPDIR} && cd ${DUMPDIR}/ || exit 1
rm -f *
cp -a ${BASEDIR}/WWW/mapserver/COPYING.gplv2 ${DUMPDIR}/COPYING

if [ ${SINGLE} = "f" ]; then
    SELECTION=`${PSQL} "SELECT fn_DlTable('${UUID}')"`
    for LAYER in ${SELECTION} ; do
        DumpSingleLayer ${LAYER}
        cp -a ${BASEDIR}/WWW/mapserver/EPSG4326.prj ${DUMPDIR}/${LAYER}\.prj
    done
    zip ${DLDIR}/${FEATURE}-${UUID}\.zip ${FEATURE}_*.* COPYING
elif [ ${SINGLE} = "t" ]; then
    DumpSingleLayer ${FEATURE} ${DUMPTABLE} "${SQLFILTER}"
    cp -a ${BASEDIR}/WWW/mapserver/EPSG4326.prj ${DUMPDIR}/${FEATURE}\.prj
    zip ${DLDIR}/${FEATURE}-${UUID}\.zip ${FEATURE}.* COPYING
fi

cd ${DUMPDIR}/.. && rm -rf ${UUID}

# EOF
