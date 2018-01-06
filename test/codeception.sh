#!/bin/bash

#set -o errexit # Exit on error

pushd ./src/server > /dev/null

echo "Running Codeception tests..."
php vendor/bin/codecept run unit
php vendor/bin/codecept run functional

popd > /dev/null


