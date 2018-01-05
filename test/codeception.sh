#!/bin/bash

#set -o errexit # Exit on error

pushd ./bibliograph/server > /dev/null

echo "Running Codeception tests..."
php vendor/bin/codecept run unit

popd > /dev/null


