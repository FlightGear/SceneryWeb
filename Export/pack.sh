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

echo "Creating Release Scenery Pack"
SCENERY_PACK=SceneryPack.SBRJ
rm -f ${SCENERY_PACK}
ln -s . ${SCENERY_PACK}
tar --create --owner=root --group=root --gzip --file=$OUTPUTDIR/${SCENERY_PACK}.tgz ${SCENERY_PACK}/[OT][be]*/w050s30/w04[34]s2[23] ${SCENERY_PACK}/Airports ${SCENERY_PACK}/Models
rm ${SCENERY_PACK}

cd "$OUTPUTDIR"

echo "rsyncing 10x10 tarballs"
rsync -a [ew][01][0-9]0[ns][0-9]0.tgz web.sourceforge.net:htdocs/scenery/

echo "rsyncing GlobalObjects.tgz and SharedModels.tgz and Release Scenery Pack"
rsync -a GlobalObjects.tgz SharedModels.tgz ${SCENERY_PACK}.tgz web.sourceforge.net:/home/frs/project/flightgear/scenery/

echo "Triggering Jenkins"

popd
