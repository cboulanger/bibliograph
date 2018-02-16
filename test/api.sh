#!/bin/bash

set -o errexit # Exit on error
YIICMD="php yii-test"
CPTCMD="php vendor/bin/codecept"

pushd ./src/server > /dev/null
echo "Setting up database ..."
$YIICMD migrate/fresh --interactive=0 --db=testdb --migrationNamespaces=app\\migrations\\schema  &> /dev/null
$YIICMD migrate/up --interactive=0 --db=testdb --migrationNamespaces=app\\migrations\\data  &> /dev/null
$YIICMD migrate/up --interactive=0 --db=testdb --migrationNamespaces=app\\tests\\migrations  &> /dev/null

echo "Running tests..."
$CPTCMD run api

echo "Cleaning up database ..."
$YIICMD migrate/down all --interactive=0 --db=testdb  &> /dev/null
popd > /dev/null