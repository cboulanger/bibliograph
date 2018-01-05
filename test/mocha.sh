#!/bin/bash

set -o errexit # Exit on error

echo "Loading data ..."
echo "travis_fold:start:data"
pushd ./bibliograph/server > /dev/null
php yii migrate/up --interactive=0 --db=testdb -p=@app/migrations/data
echo "travis_fold:end:data"
popd > /dev/null

echo "Running Mocha tests..."
npm link mocha
mocha -- ./test/**/*.test.js
