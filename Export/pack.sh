#!/bin/bash
OUTPUTDIR=/home/terrascenery/sf-projectweb/htdocs/scenery

pushd /home/terrascenery/checkout
mkdir -p "$OUTPUTDIR"

tar --create --owner=root --group=root --gzip --file "$OUTPUTDIR/SharedModels.tgz" Models
tar --create --owner=root --group=root --gzip --file "$OUTPUTDIR/GlobalObjects.tgz" Objects
for f in Objects/[ew][0-9][0-9]0[ns][0-9]0; do
  tar --create --owner=root --group=root --gzip --file "$OUTPUTDIR/$(basename $f).tgz" "$f"
done

cd "$OUTPUTDIR"

rsync -av --checksum [ew][1][0-9][0-9][ns][0-9][0-1].tgz torstendreyer,flightgear@web.sourceforge.net:htdocs/scenery/
rsync -av --checksum GlobalObjects.tgz SharedModels.tgz torstendreyer,flightgear@web.sourceforge.net:/home/frs/project/flightgear/scenery/

popd
