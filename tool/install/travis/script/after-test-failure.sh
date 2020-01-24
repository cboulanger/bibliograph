#!/usr/bin/env bash

echo " === application logs ==="
echo "travis_fold:start:logs"
cat src/server/runtime/logs/app.log
if [[ -f src/server/runtime/logs/error.log ]]; then cat src/server/runtime/logs/error.log;
fi
echo "travis_fold:end:logs"

# cleanup
aws s3 rm --recursive s3://travis.panya.de/$TRAVIS_BUILD_NUMBER
