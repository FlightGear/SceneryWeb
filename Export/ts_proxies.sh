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

PGHOST=eclipse.optiputer.net
PGDATABASE=scenemodels
FG_SCENERY=/home/fgscenery/Terrascenery

LYNX="/usr/bin/lynx -connect_timeout=5 -read_timeout=5"
PSQL="/usr/bin/psql -h ${PGHOST} -d ${PGDATABASE}"

for ID in `${PSQL} -tA -c "SELECT ogc_fid FROM ts_proxies;"`; do
    URL=`${PSQL} -tA -c "SELECT url FROM ts_proxies WHERE ogc_fid = ${ID};"`  # trailing slash !
    FILE=`find ${FG_SCENERY}/Models/ -type f | shuf -n1 | sed -e "s|${FG_SCENERY}/||g"`
    FILEURL=${URL}${FILE}
    env -u http_proxy ${LYNX} -dump ${FILEURL} > /dev/null 2>&1
    RETURN=${?}
    if [ ${RETURN} = 0 ]; then
        VALID="TRUE"
    else
        VALID="FALSE"
    fi
    SQL="UPDATE ts_proxies SET valid = ${VALID} WHERE ogc_fid = ${ID};"
    ${PSQL} -c "${SQL}" > /dev/null
done

# EOF
