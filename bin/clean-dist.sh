#!/usr/bin/env bash

DIST_DIR=$(pwd)/dist
if [[ ! -d "$DIST_DIR" ]]; then
    echo "Cannot find 'dist' subdirectory - are you in the top folder?"
fi

# dist - Client files
rm -rf $DIST_DIR/{bibliograph,resource}
rm $DIST_DIR/index.html

# dist - Server files
rm -rf $DIST_DIR/server/{config,controllers,lib,messages,migrations,models,modules,vendor,schema} 

# documentation
rm $DIST_DIR/{version.txt,readme.md,release-notes.md}

# ZIPs
rm $DIST_DIR/*.zip