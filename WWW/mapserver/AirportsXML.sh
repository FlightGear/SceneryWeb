#!/bin/sh
#
# Copyright (C) 2008 - 2012  Martin Spott - Martin (at) flightgear (dot) org
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
#

PGHOST=geoscope.optiputer.net; export PGHOST
PGDATABASE=landcover
PGUSER=webuser; export PGUSER
PSQL="psql -d ${PGDATABASE}"
SILENTSQL="${PSQL} -tA"
#
#XMLBASEPATH=${HOME}/Scenery/Airports
XMLBASEPATH=${HOME}/TestAirports
#
# Bei allen folgenden Operationen, bei denen wir mehrere Strings aus der
# Datenbank in eine Variable stecken, muessen wir den Umweg gehen, erstmal
# die 'ogc_fid' zu suchen und _anschliessend erst die Text-Repraesentation
# zu der betreffenden Nummer ermitteln.
# Tun wir das nicht, dann kommen mehrere Strings hintereinander, durch
# Leerzeichen getrennt, in eine Variable und dann gehen die Leerzeichen am
# Ende von drei-buchstabigen ICAO-Codes oder von zwei-buchstabigen Runway-
# Bezeichnern verloren.
# In diesem Zusammenhang koennte auch der Einsatz des sonst so geliebten
# 'awk' hoechst unerwuenschte Folgen haben.

# Erst machen wir alle Flugplaetze ueber die 'ogc_fid' dingfest. Unsere
# Import-Mimik fuer 'apt_airfield' setzt das Feld 'type' auf 'L', falls
# es sich _nicht_ um einen Sonderfall (Seaplane/Helipad) handelt. Das werten
# wir gleich aus, um zu bestimmen, ob eine *.threshold.xml erzeugt werden
# soll.

#APT_IDs=`${SILENTSQL} -c "SELECT ogc_fid FROM apt_airfield WHERE icao LIKE 'KDFW' ORDER BY icao"`
APT_IDs=`${SILENTSQL} -c "SELECT ogc_fid FROM apt_airfield ORDER BY icao"`
for i in ${APT_IDs}; do
  # Dann suchen wir uns die ICAO-ID's und darueber wiederum die 'ogc_fid's
  # der vorhandenen Runways zu genau diesem Platz. Erstmal die Sachlage klarmachen:
  AIRFIELD=`${SILENTSQL} -c "SELECT icao from apt_airfield WHERE ogc_fid = ${i}"`
  APT_TYPE=`${SILENTSQL} -c "SELECT type from apt_airfield WHERE icao LIKE '${AIRFIELD}'"`
  if [ ${APT_TYPE} != "S" -a ${APT_TYPE} != "H" ]; then
    APT_HAS_THR=1
  else
    APT_HAS_THR=0
  fi
  APT_HAS_ILS=`${SILENTSQL} -c "SELECT count(ogc_fid) FROM apt_ils WHERE icao LIKE '${AIRFIELD}'"`
  APT_HAS_TWR=`${SILENTSQL} -c "SELECT count(ogc_fid) FROM apt_twr WHERE icao LIKE '${AIRFIELD}'"`

  # Wir zerlegen unseren Flugplatz und braten einen Verzeichnisbaum - das
  # Verzeichnis selber legen wir aber erst dann an, wenn wir auch wirklich
  # wissen, dass da 'was 'reinkommt.
  STRIPAPT=`echo ${AIRFIELD} | tr -d \ `
  MAJOR=`echo ${STRIPAPT} | cut -c 1`
  MINOR=`echo ${STRIPAPT} | cut -c 2`
  TINY=`echo ${STRIPAPT} | cut -c 3`
  XMLPATH=${XMLBASEPATH}/${MAJOR}/${MINOR}/${TINY}

  # Thresholds haben wir fast immer
  if [ ${APT_HAS_THR} != 0 ]; then
    mkdir -p ${XMLPATH}
    THRXML=${XMLPATH}/${STRIPAPT}\.threshold.xml
    echo "<?xml version=\"1.0\"?>" > ${THRXML}
    echo "<PropertyList>" >> ${THRXML}
    THRKML=${XMLPATH}/${STRIPAPT}\.threshold.kml
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" > ${THRKML}
    echo "<kml xmlns=\"http://www.opengis.net/kml/2.2\">" >> ${THRKML}
    echo "<Document>" >> ${THRKML}
  fi
  
  # ILSse nicht notwendigerweise
  if [ ${APT_HAS_ILS} != 0 ]; then
    mkdir -p ${XMLPATH}
    ILSXML=${XMLPATH}/${STRIPAPT}\.ils.xml
    echo "<?xml version=\"1.0\"?>" > ${ILSXML}
    echo "<PropertyList>" >> ${ILSXML}
  fi
  RWY_IDs=`${SILENTSQL} -c "SELECT ogc_fid FROM apt_runway WHERE icao LIKE '${AIRFIELD}' ORDER BY rwy_num1"`
  
  # Tower auch nicht notwendigerweise
  if [ ${APT_HAS_TWR} != 0 ]; then
    mkdir -p ${XMLPATH}
    TWRXML=${XMLPATH}/${STRIPAPT}\.twr.xml
    echo "<?xml version=\"1.0\"?>" > ${TWRXML}
    echo "<PropertyList>" >> ${TWRXML}
  fi

  # Sind die 'ogc_fid's der Runways dieses einen Platzes bekannt, koennen
  # wir darueber die Benamsungen der beiden jeweiligen Bahn-Richtungen
  # ermitteln. Achtung, bei spaeteren Aktionen muessen wir immer wieder den
  # bezug zum Platz herstellen, denn eine Runway 18R gibt es moeglicherweise
  # haeufiger in unserer DB.
  #
  for j in ${RWY_IDs}; do
    RUNWAY1=`${SILENTSQL} -c "SELECT rwy_num1 FROM apt_runway WHERE ogc_fid = ${j}"`
    RUNWAY2=`${SILENTSQL} -c "SELECT rwy_num2 FROM apt_runway WHERE ogc_fid = ${j} AND rwy_num1 LIKE '${RUNWAY1}'"`

    # Sobald wir die Benamsungen der Bahnen haben (und den ICAO Code !!),
    # beschaffen wir uns damit die 'ogc_fid's der jeweiligen Schwellen - und
    # mit denen koennen wir endlich die verschiedenen Parameter zu den
    # einzelnen Schwellen selektieren.
    # Achtung, wir nehmen nur diejenigen Schwellen, die nicht 'displaced' sind.
    #
    if [ ${APT_HAS_THR} != 0 ]; then
      THR_IDs=`${SILENTSQL} -c "SELECT ogc_fid FROM apt_threshold WHERE icao LIKE '${AIRFIELD}' AND (rwy_num LIKE '${RUNWAY1}' OR rwy_num LIKE '${RUNWAY2}') AND is_displaced = 0"`
      echo "  <runway>" >> ${THRXML}
      for k in ${THR_IDs}; do
        ${SILENTSQL} -c "SELECT x(wkb_geometry), y(wkb_geometry), rwy_num, true_heading_deg, displaced_threshold_m, stopway_length_m \
          FROM apt_threshold WHERE ogc_fid = ${k} ORDER BY rwy_num" | \
        tr -d \  | awk -F\| '{print "    <threshold>\n\
      <lon>" $1 "</lon>\n\
      <lat>" $2 "</lat>\n\
      <rwy>" $3 "</rwy>\n\
      <hdg-deg>" $4 "</hdg-deg>\n\
      <displ-m>" $5 "</displ-m>\n\
      <stopw-m>" $6 "</stopw-m>\n\
    </threshold>"}' >> ${THRXML}
        ${SILENTSQL} -c "SELECT rwy_num, x(wkb_geometry), y(wkb_geometry) \
          FROM apt_threshold WHERE ogc_fid = ${k} ORDER BY rwy_num" | \
        tr -d \  | awk -F\| '{print "  <Placemark>\n\
    <name>" $1 "</name>\n\
    <Point>\n\
      <coordinates>" $2 "," $3 "</coordinates>\n\
    </Point>\n\
  </Placemark>"}' >> ${THRKML}
      done
      echo "  </runway>" >> ${THRXML}
    fi

    # Der Unterschied bei den ILSsen besteht quasi alleine darin, dass wir
    # hier die XML-Dateien ueberhaupt nur anlegen duerfen, wenn der Platz
    # auch wirklich ueber mindestens ein ILS verfuegt. Ausserdem duerfen wir
    # den entsprechenden Runway-EIntrag auch nur dann fabrizieren, wenn die
    # fragliche Bahn mindestens in einer Richtung ein ISL hat
    #
    RWY_HAS_ILS=`${SILENTSQL} -c "SELECT count(ogc_fid) FROM apt_ils WHERE icao LIKE '${AIRFIELD}' AND (rwy_num LIKE '${RUNWAY1}' OR rwy_num LIKE '${RUNWAY2}')"`
    if [ ${RWY_HAS_ILS} != 0 ]; then
      echo "  <runway>" >> ${ILSXML}
      ILS_IDs=`${SILENTSQL} -c "SELECT ogc_fid FROM apt_ils WHERE icao LIKE '${AIRFIELD}' AND (rwy_num LIKE '${RUNWAY1}' OR rwy_num LIKE '${RUNWAY2}')"`
      for k in ${ILS_IDs}; do
        ${SILENTSQL} -c "SELECT x(wkb_geometry), y(wkb_geometry), rwy_num, true_heading_deg, elevation_m, navaid_id \
          FROM apt_ils WHERE ogc_fid = ${k} ORDER BY rwy_num" | \
        tr -d \  | awk -F\| '{print "    <ils>\n\
      <lon>" $1 "</lon>\n\
      <lat>" $2 "</lat>\n\
      <rwy>" $3 "</rwy>\n\
      <hdg-deg>" $4 "</hdg-deg>\n\
      <elev-m>" $5 "</elev-m>\n\
      <nav-id>" $6 "</nav-id>\n\
    </ils>"}' >> ${ILSXML}
      done
      echo "  </runway>" >> ${ILSXML}
    fi
  done
#
  if [ ${APT_HAS_TWR} != 0 ]; then
    echo "  <tower>" >> ${TWRXML}
    TWR_ID=`${SILENTSQL} -c "SELECT ogc_fid FROM apt_twr WHERE icao LIKE '${AIRFIELD}'"`
    for l in ${TWR_ID}; do
      ${SILENTSQL} -c "SELECT x(wkb_geometry), y(wkb_geometry), hgt_tower_m \
        FROM apt_twr WHERE ogc_fid = ${l}" | \
      tr -d \  | awk -F\| '{print "    <twr>\n\
      <lon>" $1 "</lon>\n\
      <lat>" $2 "</lat>\n\
      <elev-m>" $3 "</elev-m>\n\
    </twr>"}' >> ${TWRXML}
    done
    echo "  </tower>" >> ${TWRXML}
  fi

# Die XMLle fuer Threshold und ILS koennen wir wirklich erst dann
# schliessen, wenn das "for j in ${RWY_IDs}" durchgelaufen ist.
  if [ ${APT_HAS_THR} != 0 ]; then
    echo "</PropertyList>" >> ${THRXML}
    echo "</Document>" >> ${THRKML}
    echo "</kml>" >> ${THRKML}
  fi
  if [ ${APT_HAS_ILS} != 0 ]; then
    echo "</PropertyList>" >> ${ILSXML}
  fi
  if [ ${APT_HAS_TWR} != 0 ]; then
    echo "</PropertyList>" >> ${TWRXML}
  fi
done

# Schlussendlich erstellen wir unseren Index. Aus der Altlast, als das
# 'icao'-Feld noch fest auf vier Stellen verdrahtet war und daher mitunter
# an der 4. Stelle ein Leerzeichen hatte, nehmen wir die kleine "tr"-Klausel
# mit.
# Wir unterstellen hier, dass es zumindest das Airports/-Verzeichnis bereits
# gibt.
${SILENTSQL} -c "SELECT icao, x(wkb_geometry), y(wkb_geometry) \
                 FROM apt_airfield ORDER BY x, y, icao" | tr -d "\ " > \
                 ${XMLBASEPATH}/index.txt

# EOF
