#!/bin/bash

#set -o errexit # Exit on error

pushd ./src/server > /dev/null

# echo "Running Unit tests..."
php vendor/bin/codecept run unit

echo "Running functional tests.."
#php vendor/bin/codecept run functional ReferenceControllerCest --debug
php vendor/bin/codecept run functional

popd > /dev/null


