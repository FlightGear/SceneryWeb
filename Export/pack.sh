#!/bin/bash
OUTPUTDIR=/home/terrascenery/sf-projectweb/htdocs/scenery

pushd /home/terrascenery/checkout
mkdir -p "$OUTPUTDIR"

echo "Creating SharedModels.tgz"
tar --create --owner=root --group=root --gzip --file "$OUTPUTDIR/SharedModels.tgz" Models
echo "Creating GlobalObjects.tgz"
tar --create --owner=root --group=root --gzip --file "$OUTPUTDIR/GlobalObjects.tgz" Objects

echo "Creating models per 10x10 tile tarball"
for f in Objects/[ew][01][0-9]0[ns][0-9]0; do
  tar --create --owner=root --group=root --gzip --file "$OUTPUTDIR/$(basename $f).tgz" "$f"
done

cd "$OUTPUTDIR"

echo "rsyncing 10x10 tarballs"
rsync -a [ew][01][0-9]0[ns][0-9]0.tgz web.sourceforge.net:htdocs/scenery/

echo "rsyncing GlobalObjects.tgz and SharedModels.tgz"
rsync -a GlobalObjects.tgz SharedModels.tgz web.sourceforge.net:/home/frs/project/flightgear/scenery/

popd
