#!/bin/bash

set -o errexit # Exit on error
YIICMD="php yii-test"
CPTCMD="php vendor/bin/codecept"

echo "Setting up database ..."
pushd ./src/server > /dev/null
$YIICMD migrate/fresh --interactive=0 --db=testdb --migrationNamespaces=app\\migrations\\schema  > /dev/null
$YIICMD migrate/up    --interactive=0 --db=testdb --migrationNamespaces=app\\tests\\migrations  > /dev/null

$CPTCMD run unit
$CPTCMD run functional

echo "Cleaning up database ..."
$YIICMD migrate/down all --interactive=0 --db=testdb > /dev/null
popd > /dev/null